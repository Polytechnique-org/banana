<?php
/********************************************************************************
* include/nntpcore.inc.php : NNTP subroutines
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';

/** Class NNTPCore
 *  implements some basic functions for NNTP protocol
 */
class BananaNNTPCore
{
    /** socket filehandle */
    private $ns;
    /** posting allowed */
    private $posting;
    /** last NNTP error code */
    private $lasterrorcode;
    /** last NNTP error text */
    private $lasterrortext;
    /** last NNTP result code */
    private $lastresultcode;
    /** last NNTP result */
    private $lastresulttext;

    /** debug mode */
    private $debug = false;
    private $bt    = array();

    /** constructor
     * @param $host STRING NNTP host
     * @parma $port STRING NNTP port
     * @param $timeout INTEGER socket timeout
     * @param $reader BOOLEAN sends a "MODE READER" at connection if true
     */
    public function __construct($host, $port = 119, $timeout = 120, $reader = true)
    {
        if (Banana::$debug_nntp) {
            $this->debug = true;
        }
        $this->ns            = fsockopen($host, $port, $errno, $errstr, $timeout);
        $this->lasterrorcode = $errno;
        $this->lasterrortext = $errstr;
        if (is_null($this->ns)) {
            return;
        }

        $this->checkState();
        $this->posting = ($this->lastresultcode == '200'); 
        if ($reader && $this->posting) {
            $this->execLine('MODE READER');
            $this->posting = ($this->lastresultcode == '200');
        }
        if (!$this->posting) {
            $this->quit();
        }
    }

    public function __destruct()
    {
        $this->quit();
    }

# Accessors

    public function isValid()
    {
        return !is_null($this->ns) && $this->posting;
    }

    public function lastErrNo()
    {
        return $this->lasterrorcode;
    }

    public function lastError()
    {
        return $this->lasterrortext;
    }

    public function backtrace()
    {
        if ($this->debug) {
            return $this->bt;
        } else {
            return null;
        }
    }

# Socket functions

    /** get a line from server
     * @return STRING 
     */
    private function getLine()
    {
        return rtrim(@fgets($this->ns, 1200));
    }

    /** fetch data (and on delimitor)
     * @param STRING $delim string indicating and of transmission
     */
    private function fetchResult($callback = null)
    {
        $array = Array();
        while (($result = $this->getLine()) != '.') {
            if (!is_null($callback)) {
                list($key, $result) = call_user_func($callback, $result);
                if (is_null($result)) {
                    continue;
                }
                if (is_null($key)) {
                    $array[] = $result;
                } else {
                    $array[$key] = $result;
                }
            } else {
                $array[] = $result;
            }
        }
        if ($this->debug && $this->bt) {
            $this->bt[count($this->bt) - 1]['response'] = count($array);
        }
        return $array;
    }

    /** puts a line on server
     * @param STRING $line line to put
     */
    private function putLine($line, $format = false)
    {
        if ($format) {
            $line = str_replace(array("\r", "\n"), '', $line);  
            $line .= "\r\n";
        }
        if ($this->debug) {
            $db_line = preg_replace('/PASS .*/', 'PASS *******', $line);
            $this->bt[] = array('action' => $db_line, 'time' => microtime(true));
        }
        return @fputs($this->ns, $line, strlen($line));
    }

    /** put a message (multiline)
     */
    private function putMessage($message = false)
    {
        if (is_array($message)) {
            $message = join("\n", $_message);
        }
        if ($message) {
            $message = preg_replace("/(^|\n)\./", '\1..', $message);
            $this->putLine("$message\r\n", false);
        }
        return $this->execLine('.');
    }


    /** exec a command a check result
     * @param STRING $line line to exec
     */
    private function execLine($line, $strict_state = true)
    {
        if (!$this->putLine($line, true)) {
            return null;
        }
        return $this->checkState($strict_state);
    }

    /** check if last command was successfull (read one line)
     * @param BOOL $strict indicate if 1XX codes are interpreted as errors (true) or success (false)
     */
    private function checkState($strict = true)
    {
        $result = $this->getLine();
        $this->lastresultcode = substr($result, 0, 3);
        $this->lastresulttext = substr($result, 4);
        if ($this->debug && $this->bt) {
            $trace =& $this->bt[count($this->bt) - 1];
            $trace['time']     = microtime(true) - $trace['time'];
            $trace['code']     = $this->lastresultcode;
            $trace['message']  = $this->lastresulttext;
            $trace['response'] = 0;
        }
        $c = $this->lastresultcode{0};
        if ($c == '2' || (($c == '1' || $c == '3') && !$strict)) {
            return true;
        } else {
            $this->lasterrorcode = $this->lastresultcode;
            $this->lasterrortext = $this->lastresulttext;
            return false;
        }
    }

# strict NNTP Functions [RFC 977]
# see http://www.faqs.org/rfcs/rfc977.html

    /** authentification
     * @param $user STRING login
     * @param $pass INTEGER password
     * @return BOOLEAN true if authentication was successful
     */
    protected function authinfo($user, $pass)
    {
        if ($this->execLine("AUTHINFO USER $user", false)) {
            return $this->execline("AUTHINFO PASS $pass");
        }
        return false;
    }

    /** retrieves an article
     * MSGID is a numeric ID a shown in article's headers. MSGNUM is a
     * server-dependent ID (see X-Ref on many servers) and retriving 
     * an article by this way will change the current article pointer.
     * If an error occur, false is returned. 
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return ARRAY lines of the article
     * @see body
     * @see head
     */
    protected function article($msgid = "")
    {
        if (!$this->execLine("ARTICLE $msgid")) {
            return false;
        }
        return $this->fetchResult();
    }

    /** post a message
     * if an error occur, false is returned
     * @param $_message STRING message to post
     * @return STRING MSGID of article 
     */
    protected function post($message)
    {
        if (!$this->execLine("POST ", false)) {
            return false;
        }
        if (!$this->putMessage($message)) {
            return false;
        }
        if (preg_match("/(<[^@>]+@[^@>]+>)/", $this->lastresulttext, $regs)) {
            return $regs[0];
        } else {
            return true;
        }
    }

    /** fetches the body of an article
     * params are the same as article
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return ARRAY lines of the article
     * @see article
     * @see head
     */
    protected function body($msgid = '')
    {
        if ($this->execLine("BODY $msgid")) {
            return false;
        }
        return $this->fetchResult();
    }

    /** fetches the headers of an article
     * params are the same as article
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return ARRAY lines of the article
     * @see article
     * @see body
     */
    protected function head($msgid = '')
    {
        if (!$this->execLine("HEAD $msgid")) {
            return false;
        }
        return $this->fetchResult();
    }

    /** set current group
     * @param $_group STRING 
     * @return ARRAY array : nb of articles in group, MSGNUM of first article, MSGNUM of last article, and group name
     */
    protected function group($group)
    {
        if (!$this->execLine("GROUP $group")) {
            return false;
        }
        $array = explode(' ', $this->lastresulttext);
        if (count($array) >= 4) {
            return array_slice($array, 0, 4);
        }
        return false;
    }

    /** set the article pointer to the previous article in current group
     * @return STRING MSGID of article
     * @see next
     */
    protected function last()
    {
        if (!$this->execLine("LAST ")) {
            return false;
        }
        if (preg_match("/^\d+ (<[^>]+>)/", $this->lastresulttext, $regs)) {
            return $regs[1];
        }
        return false;
    }

    /** set the article pointer to the next article in current group
     * @return STRING MSGID of article
     * @see last
     */

    protected function next()
    {
        if (!$this->execLine('NEXT ')) {
            return false;
        }
        if (preg_match("/^\d+ (<[^>]+>)/", $this->lastresulttext, $regs)) {
            return $regs[1];
        }
        return false;
    }

    /** set the current article pointer
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return BOOLEAN true if authentication was successful, error code otherwise
     * @see article
     * @see body
     */
    protected function nntpstat($msgid)
    {
        if (!$this->execLine("STAT $msgid")) {
            return false;
        }
        if (preg_match("/^\d+ (<[^>]+>)/", $this->lastresulttext, $regs)) {
            return $regs[1];
        }
        return false;
    }

    /** filter group list
     */
    private function filterGroups()
    {
        $list = $this->fetchResult();

        $groups = array();
        foreach ($list as $result) {
            list($group, $last, $first, $p) = explode(' ', $result, 4);
            if (!Banana::$boxpattern || preg_match('@' . Banana::$boxpattern . '@i', $group)) {
                $groups[$group] = array(intval($last), intval($first), $p);
            }
        }
        return $groups;
    }

    /** gets information about all active newsgroups
     * @return ARRAY group name => (MSGNUM of first article, MSGNUM of last article, NNTP flags)
     * @see newgroups
     */
    protected function listGroups()
    {
        if (!$this->execLine('LIST')) {
            return false;
        }
        return $this->filterGroups();
    }

    /** format date for news server
     * @param since UNIX TIMESTAMP
     */
    protected function formatDate($since)
    {
        return gmdate("ymd His", $since) . ' GMT';
    }

    /** get information about recent newsgroups 
     * same as list, but information are limited to newgroups created after $_since
     * @param $_since INTEGER unix timestamp
     * @param $_distributions STRING distributions 
     * @return ARRAY same format as liste
     * @see liste
     */
    protected function newgroups($since, $distributions = '')
    {
        if (!($since = $this->formatDate($since))) {
            return false;
        }
        if (!$this->execLine("NEWGROUPS $since $distributions")) {
            return false;
        }    
        return $this->filterGroups();
    }

    /** gets a list of new articles
     * @param $_since INTEGER unix timestamp
     * @parma $_groups STRING pattern of intersting groups 
     * @return ARRAY MSGID of new articles
     */
    protected function newnews($groups = '*', $since = 0, $distributions = '')
    {
        if (!($since = $this->formatDate($since))) {
            return false;
        }
        if (!$this->execLine("NEWNEWS $groups $since $distributions")) {
            return false;
        }
        return $this->fetchResult();
    }

    /** Tell the remote server that I am not a user client, but probably another news server
     * @return BOOLEAN true if sucessful
     */
    protected function slave()
    {
        return $this->execLine("SLAVE ");
    }

    /** implements IHAVE method
     * @param $_msgid STRING MSGID of article
     * @param $_message STRING article
     * @return BOOLEAN 
     */
    protected function ihave($msgid, $message = false)
    {
        if (!$this->execLine("IHAVE $msgid ")) {
            return false;
        }
        return $this->putMessage($message);
    }

    /** closes connection to server
     */
    protected function quit()
    {
        $this->execLine('QUIT');
        fclose($this->ns);
        $this->ns      = null;
        $this->posting = false;
    }

# NNTP Extensions [RFC 2980]

    /** Returns the date on the remote server
     * @return INTEGER timestamp 
     */

    protected function date()
    {
        if (!$this->execLine('DATE ', false)) {
            return false;
        }
        if (preg_match("/^(\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/", $this->lastresulttext, $r)) {
            return gmmktime($r[4], $r[5], $r[6], $r[2], $r[3], $r[1]);
        }
        return false;
    }

    /** returns group descriptions
     * @param $_pattern STRING pattern of intersting groups
     * @return ARRAY group name => description
     */

    protected function xgtitle($pattern = '*')
    {
        if (!$this->execLine("XGTITLE $pattern ")) {
            return false;
        }
        $array  = $this->fetchResult();
        $groups = array();
        foreach ($array as $result) {
            list($group, $desc) = split("[ \t]", $result, 2);
            $groups[$group] = $desc;
        }
        return $groups;
    }

    /** obtain the header field $hdr for all the messages specified
     * @param $_hdr STRING name of the header (eg: 'From')
     * @param $_range STRING range of articles 
     * @return ARRAY MSGNUM => header value
     */
    protected function xhdr($hdr, $first = null, $last = null)
    {
        if (is_null($first) && is_null($last)) {
            $range = "";
        } else {
            $range = $first . '-' . $last;
        }
        if (!$this->execLine("XHDR $hdr $range ")) {
            return false;
        }
        $array   = $this->fetchResult();
        $headers = array();
        foreach ($array as &$result) {
            @list($head, $value) = explode(' ', $result, 2);
            $headers[$head] = $value;
        }
        return $headers;
    }

    /** obtain the header field $_hdr matching $_pat for all the messages specified
     * @param $_hdr STRING name of the header (eg: 'From')
     * @param $_range STRING range of articles 
     * @param $_pat STRING pattern
     * @return ARRAY MSGNUM => header value
     */
    protected function xpat($_hdr, $_range, $_pat)
    {
        if (!$this->execLine("XPAT $hdr $range $pat")) {
            return false;
        }
        $array   = $this->fetchResult();
        $headers = array();
        foreach ($array as &$result) {
            list($head, $value) = explode(' ', $result, 2);
            $headers[$head] = $result;
        }
        return $headers;
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8: 
?>

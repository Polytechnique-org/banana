<?php
/********************************************************************************
* include/NetNNTP.inc.php : NNTP subroutines
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** Class NNTP
 *  implements some basic functions for NNTP protocol
 */
class nntp
{
    /** socket filehandle */
    var $ns;
    /** posting allowed */
    var $posting;
    /** last NNTP error code */
    var $lasterrorcode;
    /** last NNTP error text */
    var $lasterrortext;

    /** constructor
     * @param $_host STRING NNTP host
     * @param $_timeout INTEGER socket timeout
     * @param $_reader BOOLEAN sends a "MODE READER" at connection if true
     */

    function nntp($_url, $_timeout=120, $_reader=true)
    {
        $url['port'] = 119;
        $url         = parse_url($_url);
        $this->ns    = fsockopen($url['host'], $url['port'], $errno, $errstr, $_timeout);
        if (!$this->ns) {
            $this = false;
            return false;
        }

        $result        = $this->gline(); 
        $this->posting = (substr($result, 0, 3)=="200");
        if ($_reader && ($result{0}=="2")) {
            $this->pline("MODE READER\r\n");
            $result        = $this->gline();
            $this->posting = ($result{0}=="200");
        }
        if ($result{0}=="2" && $url['user'] && $url['user']!='anonymous') {
            return $this->authinfo($url['user'], $url['pass']);
        }
        return ($result{0}=="2");
    }

# Socket functions

    /** get a line from server
     * @return STRING 
     */

    function gline()
    {
        return rtrim(fgets($this->ns, 1200));
    }

    /** puts a line on server
     * @param STRING $_line line to put
     */

    function pline($_line)
    {
        return fputs($this->ns, $_line, strlen($_line));
    }

# strict NNTP Functions [RFC 977]
# see http://www.faqs.org/rfcs/rfc977.html

    /** authentification
     * @param $_user STRING login
     * @param $_pass INTEGER password
     * @return BOOLEAN true if authentication was successful
     */

    function authinfo($_user, $_pass)
    {
        $user = preg_replace("/(\r|\n)/", "", $_user);
        $pass = preg_replace("/(\r|\n)/", "", $_pass);
        $this->pline("AUTHINFO USER $user\r\n");
        $this->gline();
        $this->pline("AUTHINFO PASS $pass\r\n");
        $result=$this->gline();
        if ($result{0}!="2") {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        return true;
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

    function article($_msgid="")
    {
        $msgid = preg_replace("/(\r|\n)/", "", $_msgid);
        $this->pline("ARTICLE $msgid\r\n");
        $result = $this->gline();
        if ($result{0} != '2') {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        $result = $this->gline();
        while ($result != ".") {
            $array[] = $result;
            $result  = $this->gline();
        }
        return $array;
    }

    /** post a message
     * if an error occur, false is returned
     * @param $_message STRING message to post
     * @return STRING MSGID of article 
     */

    function post($_message)
    {
        if (is_array($_message)) {
            $message=join("\n", $_message);
        } else {
            $message=$_message;
        }
        $this->pline("POST \r\n");
        $result=$this->gline();
        if ($result{0} != '3') {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        $this->pline($message."\r\n.\r\n");
        $result = $this->gline();
        if ($result{0} != '2') {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        if ($result{0} == '2') {
            if (preg_match("/(<[^@>]+@[^@>]+>)/", $result, $regs)) {
                return $regs[0];
            } else {
                return true;
            }
        }
        return false;
    }

    /** fetches the body of an article
     * params are the same as article
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return ARRAY lines of the article
     * @see article
     * @see head
     */

    function body($_msgid="")
    {
        $msgid = preg_replace("/(\r|\n)/", "", $_msgid);
        $this->pline("BODY $msgid\r\n");
        $result = $this->gline();
        if ($result{0} != '2') {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        $array = Array();
        while (($result = $this->gline()) != ".") {
            $array[] = $result;
        }
        return $array;
    }

    /** fetches the headers of an article
     * params are the same as article
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return ARRAY lines of the article
     * @see article
     * @see body
     */

    function head($_msgid="")
    {
        $msgid = preg_replace("/(\r|\n)/", "", $_msgid);
        $this->pline("HEAD $msgid\r\n");
        $result = $this->gline();
        if ($result{0}!="2") {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        $result = $this->gline();
        while ($result != ".") {
            $array[] = $result;
            $result  = $this->gline();
        }
        return $array;
    }

    /** set current group
     * @param $_group STRING 
     * @return ARRAY array : nb of articles in group, MSGNUM of first article, MSGNUM of last article, and group name
     */

    function group($_group)
    {
        $group = preg_replace("/(\r|\n)/", "", $_group);
        $this->pline("GROUP $group\r\n");
        $line = $this->gline();
        if ($line{0}!="2") {
            $this->lasterrorcode = substr($line, 0, 3);
            $this->lasterrortext = substr($line, 4);
            return false;
        }
        if (preg_match("/^2\d{2} (\d+) (\d+) (\d+) ([^ ]+)/", $line, $regs)) {
            return array($regs[1], $regs[2], $regs[3], $regs[4]);
        }
        return false;
    }

    /** set the article pointer to the previous article in current group
     * @return STRING MSGID of article
     * @see next
     */

    function last()
    {
        $this->pline("LAST \r\n");
        $line = $this->gline();
        if ($line{0}!="2") {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        if (preg_match("/^2\d{2} \d+ <([^>]+)>/", $line, $regs)) {
            return "<{$regs[1]}>";
        }
        return false;
    }

    /** set the article pointer to the next article in current group
     * @return STRING MSGID of article
     * @see last
     */

    function next()
    {
        $this->pline("NEXT \r\n");
        $line = $this->gline();
        if ($line{0}!="2") {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        if (preg_match("/^2\d{2} \d+ <([^>]+)>/", $line, $regs)) {
            return "<{$regs[1]}>";
        }
        return false;
    }

    /** set the current article pointer
     * @param $_msgid STRING MSGID or MSGNUM of article
     * @return BOOLEAN true if authentication was successful, error code otherwise
     * @see article
     * @see body
     */

    function nntpstat($_msgid)
    {
        $msgid = preg_replace("/(\r|\n)/", "", $_msgid);
        $this->pline("STAT $msgid\r\n");
        $line  = $this->gline();
        if ($line{0}!="2") {
            $this->lasterrorcode = substr($result, 0, 3);
            $this->lasterrortext = substr($result, 4);
            return false;
        }
        if (preg_match("/^2\d{2} \d+ <([^>]+)>/", $line, $regs)) {
            return "<{$regs[1]}>";
        }
        return false;
    }

    /** returns true if posting is allowed
     * @return BOOLEAN true if posting is allowed lines 
     */

    function postok()
    {
        return ($this->posting);
    }

    /** gets information about all active newsgroups
     * @return ARRAY group name => (MSGNUM of first article, MSGNUM of last article, NNTP flags)
     * @see newgroups
     */

    function liste()
    {
        $this->pline("LIST\r\n");
        if (substr($this->gline(), 0, 1)!="2") return false;
        $result = $this->gline();
        $array = Array();
        while ($result != ".") {
            preg_match("/([^ ]+) (\d+) (\d+) (.)/", $result, $regs);
            $array[$regs[1]] = array(intval($regs[2]), intval($regs[3]), intval($regs[4]));
            $result          = $this->gline();
        }
        return $array;
    }

    /** get information about recent newsgroups 
     * same as list, but information are limited to newgroups created after $_since
     * @param $_since INTEGER unix timestamp
     * @param $_distributions STRING distributions 
     * @return ARRAY same format as liste
     * @see liste
     */

    function newgroups($_since, $_distributions="")
    {
#assume $_since is a unix timestamp
        $distributions = preg_replace("/(\r|\n)/", "", $_distributions);
        $this->pline("NEWGROUPS ".gmdate("ymd His", $_since)
                ." GMT $distributions\r\n");
        if (substr($this->gline(), 0, 1)!="2") {
            return false;
        }
        $result = $this->gline();
        $array  = array();
        while ($result != ".") {
            preg_match("/([^ ]+) (\d+) (\d+) (.)/", $result, $regs);
            $array[$regs[1]] = array(intval($regs[2]), intval($regs[3]), intval($regs[4]));
            $result          = $this->gline();
        }
        return $array;
    }

    /** gets a list of new articles
     * @param $_since INTEGER unix timestamp
     * @parma $_groups STRING pattern of intersting groups 
     * @return ARRAY MSGID of new articles
     */

    function newnews($_since, $_groups="*", $_distributions="")
    {
        $distributions = preg_replace("/(\r|\n)/", "", $_distributions);
        $groups = preg_replace("/(\r|\n)/", "", $_groups);
        $array  = array();
#assume $since is a unix timestamp
        $this->pline("NEWNEWS $_groups ".gmdate("ymd His", $_since)." GMT $distributions\r\n");
        if (substr($this->gline(), 0, 1)!="2") {
            return false;
        }
        while (($result = $this->gline()) != ".") {
            $array[] = $result;
        }
        return $array;
    }

    /** Tell the remote server that I am not a user client, but probably another news server
     * @return BOOLEAN true if sucessful
     */

    function slave()
    {
        $this->pline("SLAVE \r\n");
        return (substr($this->gline(), 0, 1)=="2");
    }

    /** implements IHAVE method
     * @param $_msgid STRING MSGID of article
     * @param $_message STRING article
     * @return BOOLEAN 
     */

    function ihave($_msgid, $_message=false)
    {
        $msgid = preg_replace("/(\r|\n)/", "", $_msgid);
        if (is_array($message)) {
            $message = join("\n", $_message);
        } else {
            $message = $_message;
        }
        $this->pline("IHAVE $msgid \r\n");
        $result = $this->gline();
        if ($message && ($result{0}=="3")) {
            $this->pline("$message\r\n.\r\n");
            $result = $this->gline();
        }
        return ($result{0}=="2");
    }

    /** closes connection to server
     */

    function quit()
    {
        $this->pline("QUIT\r\n");
        $this->gline();
        fclose($this->ns);
    }

# NNTP Extensions [RFC 2980]

    /** Returns the date on the remote server
     * @return INTEGER timestamp 
     */

    function date()
    {
        $this->pline("DATE \r\n");
        $result = $this->gline();
        if (preg_match("/^111 (\d{4})(\d{2})(\d{2})(\d{2})(\d{2})(\d{2})$/", $result, $r)) {
            return gmmktime($r[4], $r[5], $r[6], $r[2], $r[3], $r[1]);
        }
        return false;
    }

    /** returns group descriptions
     * @param $_pattern STRING pattern of intersting groups
     * @return ARRAY group name => description
     */

    function xgtitle($_pattern="*")
    {
        $pattern = preg_replace("/[\r\n]/", "", $_pattern);
        $this->pline("XGTITLE $pattern \r\n");
        if (substr($this->gline(), 0, 1)!="2") return false;
        $result = $this->gline();
        while ($result != ".") {
            preg_match("/([^ \t]+)[ \t]+(.+)$/", $result, $regs);
            $array[$regs[1]] = $regs[2];
            $result          = $this->gline();
        }
        return $array;
    }

    /** obtain the header field $hdr for all the messages specified
     * @param $_hdr STRING name of the header (eg: 'From')
     * @param $_range STRING range of articles 
     * @return ARRAY MSGNUM => header value
     */

    function xhdr($_hdr, $_range="")
    {
        $hdr    = preg_replace("/(\r|\n)/", "", $_hdr);
        $range  = preg_replace("/(\r|\n)/", "", $_range);
        $this->pline("XHDR $hdr $range \r\n");
        if (substr($this->gline(), 0, 1)!="2") {
            return false;
        }
            
        $array  = array();
        while (($result = $this->gline()) != '.') {
            preg_match("/([^ \t]+) (.*)$/", $result, $regs);
            $array[$regs[1]] = $regs[2];
        }
        return $array;
    }

    /** obtain the header field $_hdr matching $_pat for all the messages specified
     * @param $_hdr STRING name of the header (eg: 'From')
     * @param $_range STRING range of articles 
     * @param $_pat STRING pattern
     * @return ARRAY MSGNUM => header value
     */

    function xpat($_hdr, $_range, $_pat)
    {
        $hdr   = preg_replace("/(\r|\n)/", "", $_hdr);
        $range = preg_replace("/(\r|\n)/", "", $_range);
        $pat   = preg_replace("/(\r|\n)/", "", $_pat);
        $this->pline("XPAT $hdr $range $pat\r\n");
        if (substr($this->gline(), 0, 1)!="2") {
            return false;
        }
        $result = $this->gline();
        while ($result != ".") {
            preg_match("/([^ \t]+) (.*)$/", $result, $regs);
            $array[$regs[1]] = $regs[2];
            $result          = $this->gline();
        }
        return $array;
    }
}

?>

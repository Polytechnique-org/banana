<?php
/********************************************************************************
* banana/protocoleinterface.inc.php : interface for box access
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';
require_once dirname(__FILE__) . '/protocoleinterface.inc.php';
require_once dirname(__FILE__) . '/message.inc.php';

class BananaMBox implements BananaProtocoleInterface
{
    private $file         = null;
    private $filesize     = null;
    private $current_id   = null;
    private $at_beginning = false;
    private $file_cache   = null;

    private $_lasterrno = 0;
    private $_lasterror = null;

    private $count        = null;
    private $new_messages = null;
    private $messages     = null;

    /** Build a protocole handler plugged on the given box
     */
    public function __construct()
    {
        $filename = $this->getFileName(Banana::$group);
        if (is_null($filename)) {
            return;
        }
        $this->filesize = filesize($filename);
        $this->file = @fopen($filename, 'r');
        if (!$this->file) {
            $this->_lasterrno = 1;
            $this->_lasterror = _b_('Can\'t open file');
            $this->file = null;
        }
        $this->current_id   = 0;
        $this->at_beginning = true;
    }

    /** Close the file
     */
    public function __destruct()
    {
        if ($this->file) {
            fclose($this->file);
        }
    }

    /** Indicate if the Protocole handler has been succesfully built
     */
    public function isValid()
    {
        return !Banana::$group || $this->file;
    }
    
    /** Indicate last error n°
     */
    public function lastErrNo()
    {
        return $this->_lasterrno;;
    }
    
    /** Indicate last error text
     */
    public function lastError()
    {
        return $this->_lasterror;
    }

    /** Return the description of the current box
     */
    public function getDescription()
    {
        return null;
    }

    /** Return the list of the boxes
     * @param mode Kind of boxes to list
     * @param since date of last check (for new boxes and new messages)
     * @param withstats Indicated whether msgnum and unread must be set in the result
     * @return Array(boxname => array(desc => boxdescripton, msgnum => number of message, unread =>number of unread messages)
     */
    public function getBoxList($mode = Banana::BOXES_ALL, $since = 0, $withstats = false)
    {
        return array(Banana::$group => array('desc' => '', 'msgnum' => 0, 'unread' => 0));
    }

    /** Return a message
     * @param id Id of the emssage (can be either an Message-id or a message index)
     * @return A BananaMessage or null if the given id can't be retreived
     */
    public function &getMessage($id)
    {
        $message = null;
        if (!is_numeric($id)) {
            if (!Banana::$spool) {
                return $message;
            }
            $id = Banana::$spool->ids[$id];
        }
        $messages = $this->readMessages(array($id));
        if (!empty($messages)) {
            $message = new BananaMessage($messages[$id]['message']);
        }
        return $message;    
    }

    /** Return the sources of the given message
     */
    public function getMessageSource($id)
    {
        $message = null;
        if (!is_numeric($id)) {
            if (!Banana::$spool) { 
                return $message;
            }   
            $id = Banana::$spool->ids[$id];
        } 
        $message = $this->readMessages(array($id));
        return implode("\n", $message[$id]['message']);
    }   

    /** Compute the number of messages of the box
     */
    private function getCount()
    {
        $this->count = count(Banana::$spool->overview);
        $max = @max(array_keys(Banana::$spool->overview));
        if ($max && Banana::$spool->overview[$max]->storage['next'] == $this->filesize) {
            $this->new_messages = 0;
        } else {
            $this->new_messages = $this->countMessages($this->count);
            $this->count += $this->new_messages;
        }    
    }

    /** Return the indexes of the messages presents in the Box
     * @return Array(number of messages, MSGNUM of the first message, MSGNUM of the last message)
     */
    public function getIndexes()
    {
        if (is_null($this->count)) {
            $this->getCount();
        }
        return array($this->count, 0, $this->count - 1);
    }

    /** Return the message headers (in BananaMessage) for messages from firstid to lastid
     * @return Array(id => array(headername => headervalue))
     */
    public function &getMessageHeaders($firstid, $lastid, array $msg_headers = array())
    {
        $msg_headers = array_map('strtolower', $msg_headers);
        $messages =& $this->readMessages(range($firstid, $lastid), true);
        $msg_headers = array_map('strtolower', $msg_headers);
        $headers  = array();
        foreach ($msg_headers as $header) {
            foreach ($messages as $id=>&$message) {
                if (!isset($headers[$id])) {
                    $headers[$id] = array('beginning' => $message['beginning'], 'end' => $message['end']);
                }
                if ($header == 'date') {
                    $headers[$id][$header] = @strtotime($message['message'][$header]);
                } else {
                    $headers[$id][$header] = @$message['message'][$header];
                }
            }
        }
        unset($this->messages);
        unset($messages);
        return $headers;
    }

    /** Add storage data in spool overview
     */
    public function updateSpool(array &$messages)
    {
        foreach ($messages as $id=>&$data) {
            if (isset(Banana::$spool->overview[$id])) {
                Banana::$spool->overview[$id]->storage['offset'] = $data['beginning'];
                Banana::$spool->overview[$id]->storage['next']   = $data['end'];
            }
        }
    }

    /** Return the indexes of the new messages since the give date
     * @return Array(MSGNUM of new messages)
     */
    public function getNewIndexes($since)
    {
        if (is_null($this->new_messages)) {
            $this->getCount(); 
        }
        return range($this->count - $this->new_messages, $this->count - 1);
    }

    /** Return wether or not the protocole can be used to add new messages
     */
    public function canSend()
    {
        return true;
    }

    /** Return false because we can't cancel a mail
     */
    public function canCancel()
    {
        return false;
    }

    /** Return the list of requested headers
     * @return Array('header1', 'header2', ...) with the key 'dest' for the destination header
     * and 'reply' for the reply header, eg:
     * * for a mail: Array('From', 'Subject', 'dest' => 'To', 'Cc', 'Bcc', 'reply' => 'Reply-To')
     * * for a post: Array('From', 'Subject', 'dest' => 'Newsgroups', 'reply' => 'Followup-To')
     */
    public function requestedHeaders()
    {
        return Array('From', 'Subject', 'dest' => 'To', 'Cc', 'Bcc', 'reply' => 'Reply-To');
    }

    /** Send a message
     * @return true if it was successfull
     */
    public function send(BananaMessage &$message)
    {
        $headers = $message->getHeaders();
        $to      = $headers['To'];
        $subject = $headers['Subject'];
        unset($headers['To']);
        unset($headers['Subject']);
        $hdrs    = '';
        foreach ($headers as $key=>$value) {
            if (!empty($value)) {
                $hdrs .= "$key: $value\r\n";
            }    
        }
        $body = $message->get(false);
        return mail($to, $subject, $body, $hdrs);
    }

    /** Cancel a message
     * @return true if it was successfull
     */
    public function cancel(BananaMessage &$message)
    {
        return false;
    }

    /** Return the protocole name
     */
    public function name()
    {
        return 'MBOX';
    }

    /** Return the spool filename
     */
    public function filename()
    {
        @list($mail, $domain) = explode('@', Banana::$group);
        $file = "";
        if (isset($domain)) {
            $file = $domain . '_';
        }
        return $file . $mail;
    }

#######
# Filesystem functions
#######

    protected function getFileName($box)
    {
        if (is_null($box)) {
            return null;
        }
        @list($mail, $domain) = explode('@', $box);
        return Banana::$mbox_path . '/' . $mail;
    }

#######
# MBox parser
#######

    /** Go to the given message
     */
    private function goTo($id)
    {
        if ($this->current_id == $id && $this->at_beginning) {
            return true;
        }
        if ($id == 0) {
            fseek($this->file, 0);
            $this->current_id   = 0;
            $this->at_beginning = true;
            return true;
        } elseif (isset(Banana::$spool->overview[$id]) || isset($this->messages[$id])) {
            if (isset(Banana::$spool->overview[$id])) {
                $pos = Banana::$spool->overview[$id]->storage['offset'];
            } else {
                $pos = $this->messages[$id]['beginning'];
            }
            if (fseek($this->file, $pos) == 0) {
                $this->current_id   = $id;
                $this->at_beginning = true;
                return true;
            } else {
                $this->current_id = null;
                $this->_lasterrno = 2;
                $this->_lasterror = _b_('Can\'t find message ') . $id;
                return false;
            }
        } else {
            $max = @max(array_keys(Banana::$spool->overview));
            if (is_null($max)) {
                $max = 0;
            }
            if ($id <= $max && $max != 0) {
                $this->current_id = null;
                $this->_lasterrno = 3;
                $this->_lasterror = _b_('Invalid message index ') . $id;
                return false;
            }
            if (!$this->goTo($max)) {
                return false;
            }
            if (feof($this->file)) {
                $this->current_id = null;
                $this->_lasterrno = 4;
                $this->_lasterror = _b_('Requested index does not exists or file has been truncated');
                return false;
            }
            while ($this->readCurrentMessage(true) && $this->current_id < $id);
            if ($this->current_id == $id) {
                return true;
            }
            $this->current_id = null;
            $this->_lasterrno = 5;
            $this->_lasterror = _b_('Requested index does not exists or file has been truncated');
            return false;
        }
    }

    private function countMessages($from = 0)
    {
        $this->messages =& $this->readMessages(array($from), true, true);
        return count($this->messages);
    }

    /** Read the current message (identified by current_id)
     * @param needFrom_ BOOLEAN is true if the first line *must* be a From_ line
     * @param alignNext BOOLEAN is true if the buffer must be aligned at the beginning of the next From_ line
     * @return message sources (without storage data)
     */
    private function &readCurrentMessage($stripBody = false, $needFrom_ = true, $alignNext = true)
    {
        $file_cache =& $this->file_cache;
        if ($file_cache && $file_cache != ftell($this->file)) {
            $file_cache = null;
        }
        $msg        = array();
        $canFrom_   = false;
        $inBody     = false;
        while(!feof($this->file)) {
            // Process file cache
            if ($file_cache) { // this is a From_ line
                $needFrom_ = false;
                $this->at_beginning = false;
                $file_cache   = null;
                continue;
            }

            // Read a line
            $line    = rtrim(fgets($this->file), "\r\n");
            
            // Process From_ line
            if ($needFrom_ || !$msg || $canFrom_) {
                if (substr($line, 0, 5) == 'From ') { // this is a From_ line
                    if ($needFrom_) {
                        $needFrom = false;
                    } elseif (!$msg) {
                        continue;
                    } else {
                        $this->current_id++; // we are finally in the next message
                        if ($alignNext) {  // align the file pointer at the beginning of the new message
                            $this->at_beginning = true;
                            $file_cache = ftell($this->file);
                        }
                        break;
                    }
                } elseif ($needFrom_) {
                    return $msg;
                }
            }

            // Process non-From_ lines
            if (substr($line, 0, 6) == '>From ') { // remove inline From_ quotation
                $line = substr($line, 1);
            }
            if (!$stripBody || !$inBody) {
                $msg[] = $line; // add the line to the message source
            }
            $canFrom_ = empty($line); // check if next line can be a From_ line
            if ($canFrom_ && !$inBody && $stripBody) {
                $inBody = true;
            }
            $this->at_beginning = false;
        }
        if (!feof($this->file) && !$canFrom_) {
            $msg = array();
        }
        return $msg;
    }

    /** Read message with the given ids
     * @param ids ARRAY of ids to look for
     * @param strip BOOLEAN if true, only headers are retrieved
     * @param from BOOLEAN if true, process all messages from max(ids) to the end of the mbox
     * @return Array(Array('message' => message sources (or parsed message headers if $strip is true),
     *                     'beginning' => offset of message beginning,
     *                     'end' => offset of message end))
     */
    private function &readMessages(array $ids, $strip = false, $from = false)
    {
        if ($this->messages) {
            return $this->messages;
        }
        sort($ids);
        $messages = array();
        while ((count($ids) || $from) && !feof($this->file)) {
            if (count($ids)) {
                $id = array_shift($ids);
            } else {
                $id++;
            }
            if ($id != $this->current_id || !$this->at_beginning) {
                if (!$this->goTo($id)) {
                    continue;
                }
            }
            $beginning = ftell($this->file);
            $message   =& $this->readCurrentMessage($strip, false);
            if ($strip) {
                $message =& BananaMimePart::parseHeaders($message);
            }
            $end       = ftell($this->file);
            $messages[$id] = array('message' => $message, 'beginning' => $beginning, 'end' => $end);
        }
        return $messages;
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

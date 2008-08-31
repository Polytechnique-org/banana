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
    private $debug      = false;
    private $bt         = array();

    private $_lasterrno = 0;
    private $_lasterror = null;

    public function __construct()
    {
        $this->debug = Banana::$debug_mbox;
    }

    public function isValid()
    {
        return true;
        //!Banana::$group || $this->file;
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

    private function &getRawMessage($id)
    {
        $message = null;
        if (!is_numeric($id)) {
            if (!Banana::$spool) {
                return $message;
            }
            $id = Banana::$spool->ids[$id]->id;
        }
        $options = array ('-m ' . $id);
        $this->getMBoxPosition($options, $id);
        return $this->callHelper('-b', $options);
    }

    /** Return a message
     * @param id Id of the emssage (can be either an Message-id or a message index)
     * @return A BananaMessage or null if the given id can't be retreived
     */
    public function &getMessage($id)
    {
        $messages =& $this->getRawMessage($id);
        if ($messages) {
            $messages = new BananaMessage($messages);
        } else {
            $messages = null;
        }
        return $messages;
    }

    /** Return the sources of the given message
     */
    public function getMessageSource($id)
    {
        $message =& $this->getRawMessage($id);
        if ($message) {
            $message = implode("\n", $message);
        }
        return $message;
    }

    /** Compute the number of messages of the box
     */
    private function getCount()
    {
        $options = array();
        if (@filesize($this->getFileName()) == @Banana::$spool->storage['size']) {
            return max(array_keys(Banana::$spool->overview)) + 1;
        }
        $this->getMBoxPosition($options);
        $val =& $this->callHelper('-c', $options);
        if (!$val) {
            return 0;
        }
        return intval(trim($val[0]));
    }

    /** Return the indexes of the messages presents in the Box
     * @return Array(number of messages, MSGNUM of the first message, MSGNUM of the last message)
     */
    public function getIndexes()
    {
        $count = $this->getCount();
        return array($count, 0, $count - 1);
    }

    /** Return the message headers (in BananaMessage) for messages from firstid to lastid
     * @return Array(id => array(headername => headervalue))
     */
    public function &getMessageHeaders($firstid, $lastid, array $msg_headers = array())
    {
        $headers = null;
        $options = array();
        $options[] = "-m $firstid:$lastid";
        $this->getMboxPosition($options, $firstid);
        $lines =& $this->callHelper('-d', $options, $msg_headers);
        if (!$lines) {
            return $headers;
        }
        $headers = array();
        $in_message = false;
        $get_pos    = true;
        $hname      = null;
        foreach ($lines as $key=>&$line) {
            if (!$in_message) {
                if (!empty($line)) {
                    $id = intval($line);
                    $in_message = true;
                    $get_pos    = true;
                }
            } elseif ($get_pos) {
                $headers[$id] = array('beginning' => intval($line));
                $get_pos = false;
            } elseif (empty($line) && empty($hname)) {
                $in_message = false;
            } elseif (empty($hname)) {
                $hname = $line;
            } elseif ($hname == 'date') {
                $headers[$id][$hname] = @strtotime($line);
                $hname = null;
            } else {
                BananaMimePart::decodeHeader($line, $hname);
                $headers[$id][$hname] = $line;
                $hname = null;
            }
            unset($lines[$key]);
        }
        return $headers;
    }

    /** Add storage data in spool overview
     */
    public function updateSpool(array &$messages)
    {
        foreach ($messages as $id=>&$data) {
            if (isset(Banana::$spool->overview[$id])) {
                Banana::$spool->overview[$id]->storage['offset'] = $data['beginning'];
            }
        }
        Banana::$spool->storage['size'] = @filesize($this->getFileName());
    }

    /** Return the indexes of the new messages since the give date
     * @return Array(MSGNUM of new messages)
     */
    public function getNewIndexes($since)
    {
        $this->open();
        if (is_null($this->file)) {
            return array();
        }
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

    /** Return the execution backtrace
     */
    public function backtrace()
    {
        if ($this->debug) {
            return $this->bt;
        } else {
            return null;
        }
    }

#######
# Filesystem functions
#######

    protected function getFileName()
    {
        if (is_null(Banana::$group)) {
            return null;
        }
        @list($mail, $domain) = explode('@', Banana::$group);
        return Banana::$mbox_path . '/' . $mail;
    }

#######
# MBox parser
#######

    /** Add the '-p' optioin for callHelper
     */
    private function getMBoxPosition(array &$options, $id = null)
    {
        if (Banana::$spool && Banana::$spool->overview) {
            if (!is_null($id) && isset(Banana::$spool->overview[$id])) {
                $key = $id;
            } else {
                $key = max(array_keys(Banana::$spool->overview));
                if (!is_null($id) && $key >= $id) {
                    return;
                }
            }
            if (isset(Banana::$spool->overview[$key]->storage['offset'])) {
                $options[] = '-p ' . $key . ':' . Banana::$spool->overview[$key]->storage['offset'];
            }
        }
    }

    private function &callHelper($action, array $options = array(), array $headers = array())
    {
        $action .= ' -f ' . $this->getFileName();
        $cmd = Banana::$mbox_helper . " $action " . implode(' ', $options) . ' ' . implode(' ', $headers);
        if ($this->debug) {
            $start = microtime(true);
        }
        exec($cmd, $out, $return);
        if ($this->debug) {
            $this->bt[] = array('action' => $cmd, 'time' => (microtime(true) - $start),
                                'code' => $return, 'response' => count($out), 'error' => $return ? "Helper failed" : null);
        }
        if ($return != 0) {
            $this->_lasterrorno = 1;
            $this->_lasterrorcode = "Helper failed";
            $out = null;
        }
        return $out;
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

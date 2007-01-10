<?php
/********************************************************************************
* banana/nntp.inc.php : NNTP protocole handler
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';
require_once dirname(__FILE__) . '/message.inc.php';
require_once dirname(__FILE__) . '/nntpcore.inc.php';
require_once dirname(__FILE__) . '/protocoleinterface.inc.php';

class BananaNNTP extends BananaNNTPCore implements BananaProtocoleInterface
{
    private $description = null;
    private $ingroup = null;

    private $mode = null;
    private $boxes = null;

    /** Build a protocole handler plugged on the given box
     */
    public function __construct()
    {
        $url = parse_url(Banana::$nntp_host);
        if ($url['scheme'] == 'nntps' || $url['scheme'] == 'snntp') {
            $url['host'] = 'ssl://' . $url['host'];
        }
        if (!isset($url['port'])) {
            $url['port'] = 119;
        }
        if (!isset($url['user'])) {
            parent::__construct($url['host'], $url['port']);
        } else {
            parent::__construct($url['host'], $url['port'], 120, false);
            $this->authinfo($url['user'], $url['pass']);
        }      
    }

    /** Return the descript;ion of the current box
     */
    public function getDescription()
    {
        if ($this->description) {
            return $this->description;
        }
        $descs = $this->xgtitle(Banana::$group);
        if (isset($descs[Banana::$group])) {
            $this->description = $descs[Banana::$group];
        }
        return $this->description;
    }

    /** Return the list of the boxes
     * @param mode Kind of boxes to list
     * @param since date of last check (for new boxes and new messages)
     * @return Array(boxname => array(desc => boxdescripton, msgnum => number of message, unread =>number of unread messages)
     */
    public function getBoxList($mode = Banana::BOXES_ALL, $since = 0, $withstats = false)
    {
        if (!is_array($this->boxes) || $this->mode != $mode) {
            $descs = $this->xgtitle();
            if ($mode == Banana::BOXES_NEW && $since) {
                $list = $this->newgroups($since);
            } else {
                $list = $this->listGroups();
                if ($mode == Banana::BOXES_SUB) {
                    $sub = array_flip(Banana::$profile['subscribe']);
                    $list = array_intersect_key($list, $sub);
                }
            }
            $this->boxes = array();
            foreach ($list as $group=>&$infos) {
                if (isset($descs[$group])) {
                    $desc = $descs[$group];
                    if (!is_utf8($desc)) {
                        $desc = utf8_encode($desc);
                    }
                    $this->boxes[$group] = array('desc' => $desc);           
                } else {
                    $this->boxes[$group] = array('desc' => null);
                }    
            }
            ksort($this->boxes);
        }
        if ($withstats) {
            foreach ($this->boxes as $group=>&$desc) {
                list($msgnum, $first, $last, $groupname) = $this->group($group);
                $this->ingroup = $group;
                $new = count($this->newnews($group, $since));
                $desc['msgnum'] = $msgnum;
                $desc['unread'] = $new;
            }
        }
        return $this->boxes;
    }

    /** Return a message
     * @param id Id of the emssage (can be either an Message-id or a message index)
     * @param msg_headers Headers to process
     * @param is_msgid If is set, $id is en Message-Id
     * @return A BananaMessage or null if the given id can't be retreived
     */
    public function getMessage($id, array $msg_headers = array(), $is_msgid = false)
    {
        if (!$is_msgid && Banana::$group != $this->ingroup) {
            if (is_null(Banana::$spool)) {
                $this->group(Banana::$group);
                $this->ingroup = Banana::$group;
            } else {
                $id = array_search($id, Banana::$spool->ids);
            }
        }
        $data = $this->article($id);
        if ($data !== false) {
            return new BananaMessage($data);
        }
        return null;
    }

    /** Return the indexes of the messages presents in the Box
     * @return Array(number of messages, MSGNUM of the first message, MSGNUM of the last message)
     */
    public function getIndexes()
    {
        list($msgnum, $first, $last, $groupname) = $this->group(Banana::$group);
        $this->ingroup = Banana::$group;
        return array($msgnum, $first, $last);
    }

    /** Return the message headers (in BananaMessage) for messages from firstid to lastid
     * @return Array(id => array(headername => headervalue))
     */
    public function &getMessageHeaders($firstid, $lastid, array $msg_headers = array())
    {
        $messages = array();
        foreach ($msg_headers as $header) {
            $headers = $this->xhdr($header, $firstid, $lastid);
            array_walk($headers, array('BananaMimePart', 'decodeHeader'));
            $header  = strtolower($header);
            if ($header == 'date') {
                $headers = array_map('strtotime', $headers);
            }
            foreach ($headers as $id=>&$value) {
                if (!isset($messages[$id])) {
                    $messages[$id] = array();
                }
                $messages[$id][$header] =& $value;
            }
        }
        return $messages;
    }

    /** Add protocole specific data in the spool
     */
    public function updateSpool(array &$messages)
    {
        return true;
    }

    /** Return the indexes of the new messages since the give date
     * @return Array(MSGNUM of new messages)
     */
    public function getNewIndexes($since)
    {
        return $this->newnews(Banana::$group, $since);
    }

    /** Return true if can post
     */
    public function canSend()
    {
        return $this->isValid();
    }

    /** Return true if can cancel
     */
    public function canCancel()
    {
        return $this->isValid();
    }

    /** Return the list of requested header for a new post
     */
    public function requestedHeaders()
    {
        return Array('From', 'Subject', 'dest' => 'Newsgroups', 'reply' => 'Followup-To', 'Organization');
    }

    /** Send the message
     */
    public function send(BananaMessage &$message)
    {
        $sources = $message->get(true);
        return $this->post($sources);
    }

    /** Cancel the message
     */
    public function cancel(BananaMessage &$message)
    {
        $headers = Array('From' => Banana::$profile['From'],
                         'Newsgroups' => Banana::$group,
                         'Subject'    => 'cmsg ' . $message->getHeaderValue('message-id'),
                         'Control'    => 'cancel ' . $message->getHeaderValue('message-id'));
        $headers = array_merge($headers, Banana::$msgedit_headers);
        $body   = 'Message canceled with Banana';
        $msg    = BananaMessage::newMessage($headers, $body);
        return $this->send($msg);
    }

    /** Return the protocole name
     */
    public function name()
    {
        return 'NNTP';
    }

    /** Return the filename for the spool
     */
    public function filename()
    {
        $url  = parse_url(Banana::$nntp_host);
        $file = '';
        if (isset($url['host'])) {
            $file .= $url['host'] . '_';
        }
        if (isset($url['port'])) {
            $file .= $url['port'] . '_';
        }
        $file .= Banana::$group;
        return $file;
    }
}

// vim:set et sw=4 sts=4 ts=4:
?>

<?php
/********************************************************************************
* banana/protocoleinterface.inc.php : interface for box access
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';

interface BananaProtocoleInterface
{
    /** Build a protocole handler plugged on the given box
     */
    public function __construct();

    /** Indicate if the Protocole handler has been succesfully built
     */
    public function isValid();
    
    /** Indicate last error n°
     */
    public function lastErrNo();
    
    /** Indicate last error text
     */
    public function lastError();

    /** Return the description of the current box
     */
    public function getDescription();

    /** Return the list of the boxes
     * @param mode Kind of boxes to list
     * @param since date of last check (for new boxes and new messages)
     * @param withstats Indicated whether msgnum and unread must be set in the result
     * @return Array(boxname => array(desc => boxdescripton, msgnum => number of message, unread =>number of unread messages)
     */
    public function getBoxList($mode = Banana::BOXES_ALL, $since = 0, $withstats = false);

    /** Return a message
     * @param id Id of the emssage (can be either an Message-id or a message index)
     * @return A BananaMessage or null if the given id can't be retreived
     */
    public function &getMessage($id);

    /** Return the sources of a message
     * @param id Id of the emssage (can be either an Message-id or a message index)
     * @return The sources of the message (or null)
     */
    public function getMessageSource($id); 

    /** Return the indexes of the messages presents in the Box
     * @return Array(number of messages, MSGNUM of the first message, MSGNUM of the last message)
     */
    public function getIndexes();

    /** Return the message headers (in BananaMessage) for messages from firstid to lastid
     * @return Array(id => array(headername => headervalue))
     */
    public function &getMessageHeaders($firstid, $lastid, array $msg_headers = array());

    /** Update the spool to add protocole specifics data
     * @param Array(id => message headers)
     */
    public function updateSpool(array &$messages);

    /** Return the indexes of the new messages since the give date
     * @return Array(MSGNUM of new messages)
     */
    public function getNewIndexes($since);

    /** Return wether or not the protocole can be used to add new messages
     */
    public function canSend();

    /** Return wether or not the protocole allow message deletion
     */
    public function canCancel();

    /** Return the list of requested headers
     * @return Array('header1', 'header2', ...) with the key 'dest' for the destination header
     * and 'reply' for the reply header, eg:
     * * for a mail: Array('From', 'Subject', 'dest' => 'To', 'Cc', 'Bcc', 'reply' => 'Reply-To')
     * * for a post: Array('From', 'Subject', 'dest' => 'Newsgroups', 'reply' => 'Followup-To')
     */
    public function requestedHeaders();

    /** Send a message
     * @return true if it was successfull
     */
    public function send(BananaMessage &$message);

    /** Cancel a message
     * @return true if it was successfull
     */
    public function cancel(BananaMessage &$message);

    /** Return the protocole name
     */
    public function name();

    /** Return the spool filename to use for the given box
     * @param box STRING boxname
     */
    public function filename();

    /** Return the execution backtrace of the protocole
     * @return array(trace1, trace2, ...)
     * a trace has the following structure:
     *  array('action' => action, 'time' => microtime, 'code' => return code, 'response' => size of the response)
     * if no backtrace is available, return null
     */
    public function backtrace();
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

<?php
/********************************************************************************
* banana/feed.inc.php : Feed Builder
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';

define('BANANA_FEED_VERSION', '0.1');

class BananaFeed
{
    /** Structure version
     */
    private $version;

    /** Feed name
     */
    public $group;

    /** Feed description
     */
    public $description;

    /** A 'ordered by id' spool of the last messages
     * Each Message is an array with :
     *  array('author' => author, 'date' => date (UNIX TS), 'title' => subject, 'body' => html body,
     *        'link' => link, 'reply' => reply)
     */
    public $messages = array();

    /** Last update time
     */
    public $lastupdate = 0;

    /** Create an empty feed
     */
    private function __construct()
    {
        $this->version     = BANANA_FEED_VERSION;
        $this->group       = Banana::$group;
        $this->description = trim(Banana::$protocole->getDescription());
    }

    /** Update the feed, using current settings of Banana
     * Calling this function suppose that Banana::$spool is the spool of the current box
     */
    public function update()
    {
        if (!Banana::$spool || Banana::$spool->group != $this->group) {
            return false;
        }
        if (!Banana::$spool->ids) {
            $spool_indexes = array();
        } else {
            $spool_indexes = Banana::$spool->ids;
            sort($spool_indexes, SORT_NUMERIC);
            $spool_indexes = array_slice($spool_indexes, -Banana::$feed_size, Banana::$feed_size);
        }    
        $feed_indexes  = array_keys($this->messages);
        $old = array_diff($feed_indexes, $spool_indexes);
        foreach ($old as $key) {
            unset($this->messages[$key]);
        }
        $new = array_diff($spool_indexes, $feed_indexes);
        foreach ($new as $key) {
            $message =& Banana::$protocole->getMessage($key);
            $array = array();
            $array['author'] = $message->getAuthorName();
            $array['date']   = $message->getHeaderValue('Date');
            $array['title']  = $message->getHeaderValue('Subject');
            $array['body']   = $message->getFormattedBody();
            $array['link']   = Banana::$page->makeUrl(array('group' => $this->group, 'artid' => $key));
            if (Banana::$protocole->canSend()) {
                $array['reply'] = Banana::$page->makeUrl(array('group' => $this->group, 'artid' => $key, 'action' => 'new'));
            }
            $this->messages[$key] = $array;
        }
        uasort($this->messages, Array('BananaFeed', 'compare'));
        $this->lastupdate = time();
        $this->writeToFile();
    }

    /** Get the spool corresponding with the current settings of Banana
     */
    static public function &getFeed()
    {
        $feed =& BananaFeed::readFromFile();
        if (!$feed) {
            $feed = new BananaFeed();
        }
        if (Banana::$feed_updateOnDemand) {
            $feed->update();
        }
        return $feed;
    }

    /** Return the cache file name
     */
    static private function filename()
    {
        $file = Banana::$spool_root . '/' . Banana::$protocole->name() . '/';
        if (!is_dir($file)) {
            mkdir($file);
        }
        return $file . Banana::$protocole->filename() . '_feed';
    }

    /** Read a feed from a cache file
     */
    static private function &readFromFile()
    {
        $feed = null;
        $file = BananaFeed::filename();
        if (!file_exists($file)) {
            return $feed;
        }
        $feed = unserialize(file_get_contents($file));
        if ($feed->version != BANANA_FEED_VERSION) {
            $feed = null;
        }
        return $feed;
    }

    /** Write a feed to a cache file
     */
    private function writeToFile()
    {
        $file = BananaFeed::filename();
        file_put_contents($file, serialize($this));
    }

    /** Merge to feeds into a new one
     */
    static public function &merge(&$feed1, &$feed2, $name, $description = null)
    {
        if (!$feed1) {
            $feed  = null;
            $feed1 =& $feed2;
            $feed2 =& $feed;
        }
        if ($feed1->group == $name) {
            $master =& $feed1;
            $slave  =& $feed2;
        } else if ($feed2 && $feed2->group == $name) {
            $master =& $feed2;
            $slave  =& $feed1;
        } else {
            $master = new BananaFeed();
            $master->group       = $name;
            $master->description = $description;
            foreach ($feed1->messages as $key=>$message) {
                $message['title'] = '[' . $feed1->group . '] ' . $message['title'];
                $master->messages[$feed1->group . '-' . $key] = $message;
            }
            $slave =& $feed2;
        }
        if (!$slave) {
            return $master;
        }
        $messages = array();
        $m1       = end($master->messages);
        $m2       = end($slave->messages);
        for ($i = 0 ; $i < 2 * Banana::$feed_size && ($m1 || $m2) ; $i++) {
            if ($m2 && (!$m1 || $m1['date'] < $m2['date'])) {
                $m2['title'] = '[' . $feed2->group . '] ' . $m2['title'];
                $messages[$slave->group . '-' . key($slave->messages)] = $m2;
                $m2 = prev($slave->messages);
            } else {
                $messages[key($master->messages)] = $m1;
                $m1 = prev($master->messages);
            }
        }
        uasort($messages, array('BananaFeed', 'compare'));
        $master->messages =& $messages;
        $master->lastupdate = time();
        return $master;
    }

    static function compare($a, $b)
    {
        if ($a['date'] == $b['date']) {
            return 0;
        }
        return $a['date'] < $b['date'] ? -1  : 1;
    }

    /** Generate the feed xml
     */
    public function toXML()
    {
        Banana::$page->assign_by_ref('feed', $this);
        return Banana::$page->feed();
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

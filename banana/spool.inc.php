<?php
/********************************************************************************
* include/spool.inc.php : spool subroutines
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';
require_once dirname(__FILE__) . '/tree.inc.php';

define('BANANA_SPOOL_VERSION', '0.5.14');

/** Class spoolhead
 *  class used in thread overviews
 */
class BananaSpoolHead
{
    public $id;
    public $msgid;

    /** date (timestamp) */
    public $date;
    /** subject */
    public $subject;
    /** author */
    public $from;
    public $name;
    public $color;
    /** reference of parent */
    public $parent = null;
    /** array of children */
    public $children = Array();
    /** true if post is read */
    public $isread;
    /** number of posts deeper in this branch of tree */
    public $desc;
    /**  same as desc, but counts only unread posts */
    public $descunread;
    /** last time the number of children has been updated */
    public $time = 0;

    /** storage data */
    public $storage = array();

    /** constructor
     * @param $_date INTEGER timestamp of post
     * @param $_subject STRING subject of post
     * @param $_from STRING author of post
     * @param $_desc INTEGER desc value (1 for a new post)
     * @param $_read BOOLEAN true if read
     * @param $_descunread INTEGER descunread value (0 for a new post)
     */
    public function __construct($id, array &$message)
    {
        $this->id         = $id;
        $this->msgid      = @$message['message-id'];
        $this->date       = $message['date'];
        $this->subject    = @$message['subject'];
        $this->from       = $message['from'];
        $this->color      = sprintf('#%06x', abs(crc32($this->from) % 0xffffff));
        $this->desc       = 1;
        $this->isread     = true;
        $this->descunread = 0;
        if (preg_match("/^([^ ]+@[^ ]+) \((.*)\)$/", $this->from, $regs)) {
            $this->name = $regs[2];
        }
        if (preg_match("/^\"?([^<>\"]+)\"? +<(.+@.+)>$/", $this->from, $regs)) {
            $this->name = preg_replace("/^'(.*)'$/", '\1', $regs[1]);
            $this->name = stripslashes($this->name);
        }
        if ($this->name) {
            $this->name =  preg_replace("/\\\(\(|\))/","\\1", $this->name);
        } else if (preg_match("/([^< ]+)@([^> ]+)/", $this->from, $regs)) {
            $this->name = $regs[1];
        } else {
            $this->name = 'Anonymous';
        }
    }
}


class BananaSpool
{
    private $version;
    private $mode;

    /**  group name */
    public $group;
    /**  spool */
    public $overview = array();
    /**  array msgid => msgnum */
    public $ids      = array();
    /** thread starts */
    public $roots    = array();

    /** protocole specific data */
    public $storage = array();

    private $unreadnb = 0;

    /** constructor
     * @param $_group STRING group name
     * @param $_display INTEGER 1 => all posts, 2 => only threads with new posts
     * @param $_since INTEGER time stamp (used for read/unread)
     */
    protected function __construct($group)
    {
        $this->version    = BANANA_SPOOL_VERSION;
        $this->mode       = Banana::SPOOL_ALL;
        $this->group      = $group;
    }

    public static function &getSpool($group, $since = 0, $clean = false)
    {
        if (!is_null(Banana::$spool) && Banana::$spool->group == $group) {
            $spool =& Banana::$spool;
        } else {
            $spool =& BananaSpool::readFromFile($group);
        }
        if (is_null($spool)) {
            $spool = new BananaSpool($group);
        }
        Banana::$spool =& $spool;
        $spool->build();
        if ($clean) {
            $spool->markAllAsRead();
        }
        $spool->updateUnread($since);
        return $spool;
    }

    public static function getPath($file = null)
    {
        $path = Banana::$spool_root . '/' . Banana::$protocole->name() . '/' . Banana::$protocole->filename();
        if (!is_dir($path)) {
            if (file_exists($path)) {
                @unlink($path);
            }
            mkdir($path, 0777, true);
        }
        return $path . '/' . $file;
    }

    private static function spoolFilename()
    {
        return BananaSpool::getPath('spool');
    }

    private static function &readFromFile($group)
    {
        $spool = null;
        $file = BananaSpool::spoolFilename();
        if (!file_exists($file)) {
            return $spool;
        }
        $spool =  unserialize(file_get_contents($file));
        if ($spool->version != BANANA_SPOOL_VERSION || $spool->mode != Banana::SPOOL_ALL) {
            $spool = null;
            return $spool;
        }
        $spool->markAllAsRead();
        return $spool;
    }

    private function compare(&$a, &$b)
    {
        return ($b->date - $a->date);
    }

    private function saveToFile()
    {
        $file = BananaSpool::spoolFilename();

        $this->roots = Array();
        foreach($this->overview as &$msg) {
            if (is_null($msg->parent)) {
                $this->roots[] =& $msg;
            }
        }
        usort($this->roots, array($this, 'compare'));

        if ($this->mode == Banana::SPOOL_ALL) {
            file_put_contents($file, serialize($this));
        }
    }

    private function build()
    {
        $threshold = 0;

        // Compute the range of indexes
        list($msgnum, $first, $last) = Banana::$protocole->getIndexes();
        if ($last < $first) {
            $threshold = $first + $msgnum - $last;
            $threshold = (int)(log($threshold)/log(2));
            $threshold = (2 ^ ($threshold + 1)) - 1;
        }
        if (Banana::$spool_max && Banana::$spool_max < $msgnum) {
            $first = $last - Banana::$spool_max;
            if ($first < 0) {
                $first += $threshold;
            }
        }
        $clean  = $this->clean($first, $last, $msgnum);
        $update = $this->update($first, $last, $msgnum);

        if ($clean || $update) {
            $this->saveToFile();
        }
    }

    private function clean(&$first, &$last, $msgnum)
    {
        $do_save = false;
        if (!empty($this->overview)) {
            $mids = array_keys($this->overview);
            foreach ($mids as $id) {
                if (($first <= $last && ($id < $first || $id > $last))
                        || ($first > $last && $id < $first && $id > $last)) {
                    $this->delid($id, false);
                    $do_save = true;
                }
            }
            if (!empty($this->overview)) {
                $first = max(array_keys($this->overview))+1;
            }
        }
        return $do_save;
    }

    private function update(&$first, &$last, $msgnum)
    {
        if ($first > $last || !$msgnum) {
            return false;
        }

        $messages =& Banana::$protocole->getMessageHeaders($first, $last,
            array('Date', 'Subject', 'From', 'Message-ID', 'References', 'In-Reply-To'));

        // Build all the new Spool Heads
        $time = time();
        foreach ($messages as $id=>&$message) {
            if (!isset($this->overview[$id])) {
                $this->overview[$id] = new BananaSpoolHead($id, $message);
                $head =& $this->overview[$id];
                $this->ids[$head->msgid] =& $head;
                $head->time = $time;
            }
        }

        // Build tree
        $null = null;
        foreach ($messages as $id=>&$message) {
            $msg         =& $this->overview[$id];
            $parents     =& $this->getReferences($message);
            while (!empty($parents) && ($msg->parent === $msg || is_null($msg->parent))) {
                @$msg->parent =& array_pop($parents);
            }

            if (!is_null($msg->parent)) {
                $parent =& $msg->parent;
                $parent->children[] =& $msg;
                while (!is_null($parent)) {
                    $parent->desc += $msg->desc;
                    $parent->time  = $time;
                    $prev =& $parent;
                    if ($parent !== $parent->parent) {
                        $parent =& $parent->parent;
                    } else {
                        $parent =& $null;
                    }
                }
            }
        }
        Banana::$protocole->updateSpool($messages);
        return true;
    }

    public function updateUnread($since)
    {
        if (empty($since)) {
            return;
        }

        $newpostsids = Banana::$protocole->getNewIndexes($since);

        if (empty($newpostsids)) {
            return;
        }

        $newpostsids = array_intersect($newpostsids, array_keys($this->ids));
        foreach ($newpostsids as $mid) {
            $overview =& $this->ids[$mid];
            if ($overview->isread) {
                $overview->isread = false;
                while (!is_null($overview)) {
                    $overview->descunread++;
                    $overview =& $overview->parent;
                }
            }
        }
        $this->unreadnb += count($newpostsids);
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
        switch ($mode) {
          case Banana::SPOOL_UNREAD:
            $num = max(array_keys($this->overview));
            if ($this->overview[$num]->isread) {
                break;
            }
            foreach ($this->roots as &$root) {
                if ($root->descunread == 0) {
                    $this->killdesc($root->id);
                }
            }
            break;
        }
    }

    /** Fetch list of references
     */
    public function &getReferences(array &$refs)
    {
        $references = array();
        if (isset($refs['references'])) {
            $text = preg_split('/\s/', str_replace('><', '> <', $refs['references']));
            foreach ($text as $id=>&$value) {
                if (isset($this->ids[$value])) {
                    $references[] =& $this->ids[$value];
                }
            }
        } elseif (isset($refs['in-reply-to']) && isset($this->ids[$refs['in-reply-to']])) {
            $references[] =& $this->ids[$refs['in-reply-to']];
        }
        return $references;
    }

    /** Get the tree associated to a given id
     */
    public function &getTree($id)
    {
        return BananaTree::build($id)->show();
    }

    /** Mark the given id as read
     * @param id MSGNUM of post
     */
    public function markAsRead($id)
    {
        $overview =& $this->overview[$id];
        if (!$overview->isread) {
            $overview->isread = true;
            $this->unreadnb--;
            while (!is_null($overview)) {
                $overview->descunread--;
                $overview =& $overview->parent;
            }
        }
    }

    /** Mark all unread messages as read
     */
    public function markAllAsRead(array &$array = null)
    {
        if (!$this->unreadnb) {
            return;
        }
        if (is_null($array) && !empty($this->roots)) {
            $array =& $this->roots;
        } elseif (is_null($array)) {
            return;
        }
        foreach ($array as &$msg) {
            if (!$msg->isread) {
                $this->markAsRead($msg->id);
                if (!$this->unreadnb) {
                    return;
                }
            }
            if ($msg->descunread) {
                $this->markAllAsRead($msg->children);
            }
        }
    }

    /** kill post and childrens
     * @param $_id MSGNUM of post
     */
    private function killdesc($_id)
    {
        $overview =& $this->overview[$_id];
        $children =& $overview->children;
        if (sizeof($children)) {
            foreach ($children as &$c) {
                $this->killdesc($c->id);
            }
        }
        unset($this->overview[$_id]);
        foreach ($this->roots as $k=>&$root) {
            if ($root === $overview) {
                unset($this->roots[$k]);
                break;
            }
        }
        unset($this->ids[$overview->msgid]);
        $overview = null;
    }

    /** delete a post from overview
     * @param $_id MSGNUM of post
     */
    public function delid($_id, $write = true)
    {
        if (!isset($this->overview[$_id])) {
            return;
        }

        $overview =& $this->overview[$_id];
        // Be sure it is not counted as unread
        if (!$overview->isread) {
            $this->markAsRead($_id);
        }

        $parent =& $overview->parent;

        // Remove from the message tree
        if (!is_null($parent)) {
            $time = time();
            foreach ($parent->children as $key=>&$child) {
                if ($child === $overview) {
                    unset($parent->children[$key]);
                    break;
                }
            }
            if (sizeof($overview->children)) {
                foreach ($overview->children as &$child) {
                    $parent->children[] =& $child;
                    $child->time   = $time;
                    $child->parent =& $parent;
                }
            }
            while (!is_null($parent)) {
                $parent->desc--;
                $parent->time = $time;
                $parent =& $parent->parent;
            }
        }

        // Remove all refenrences and assign null to the object
        unset($this->ids[$overview->msgid]);
        unset($this->overview[$_id]);
        BananaTree::kill($_id);
        foreach ($this->roots as $k=>&$root) {
            if ($root === $overview) {
                unset($this->roots[$k]);
                break;
            }
        }
        $overview = null;

        if ($write) {
            $this->saveToFile();
        }
    }

    public function formatDate(BananaSpoolHead &$head)
    {
        $stamp  = $head->date;
        $today  = intval(time() / (24*3600));
        $dday   = intval($stamp / (24*3600));

        if ($today == $dday) {
            $format = "%H:%M";
        } elseif ($today == 1 + $dday) {
            $format = _b_('hier')." %H:%M";
        } elseif ($today < 7 + $dday) {
            $format = '%a %H:%M';
        } elseif ($today < 90 + $dday) {
            $format = '%a %e %b';
        } else {
            $format = '%a %e %b %Y';
        }
        return strftime($format, $stamp);
    }

    public function formatSubject(BananaSpoolHead &$head)
    {
        $subject = $popup = $head->subject;
        $popup = $subject;
        if (function_exists('hook_formatDisplayHeader')) {
            list($subject, $link) = hook_formatDisplayHeader('subject', $subject, true);
        } else {
            $subject = banana_catchFormats(banana_entities(stripslashes($subject)));
            $link = null;
        }
        if (empty($subject)) {
            $subject = _b_('(pas de sujet)');
        }
        if ($head->id !== Banana::$artid) {
            $subject = Banana::$page->makeLink(Array('group' => $this->group, 'artid' => $head->id,
                                                     'text'  => $subject, 'popup' => $popup));
        }
        return $subject . $link;
    }

    public function formatFrom(BananaSpoolHead &$head)
    {
        return BananaMessage::formatFrom($head->from);
    }

    public function start()
    {
        if (Banana::$first) {
            return Banana::$first;
        } else {
            $first = array_search(Banana::$artid, $this->roots);
            return max(0, $first - Banana::$spool_tbefore);
        }
    }

    public function context()
    {
        return Banana::$first ? Banana::$spool_tmax : Banana::$spool_tcontext;
    }

    /** Return root message of the given thread
     * @param id INTEGER id of a message
     */
    public function &root($id)
    {
        $parent =& $this->overview[$id];
        while (!is_null($parent->parent)) {
            $parent =& $parent->parent;
        }
        return $parent;
    }

    /** Return the last post id with the given subject
     * @param subject
     */
    public function getPostId($subject)
    {
        $subject = trim($subject);
        $id = max(array_keys($this->overview));
        while (isset($this->overview[$id])) {
            $test = $this->overview[$id]->subject;
            if (function_exists('hook_formatDisplayHeader')) {
                $val = hook_formatDisplayHeader('subject', $test, true);
                if (is_array($val)) {
                    $test = banana_html_entity_decode($val[0]);
                } else {
                    $test = banana_html_entity_decode($val);
                }
            }
            $test = trim($test);
            if ($test == $subject) {
                return $id;
            }
            $id--;
        }
        return -1;
    }

    /** Returns previous thread root index
     * @param id INTEGER message number
     */
    public function prevThread($id)
    {
        $root =& $this->root($id);
        $last = null;
        foreach ($this->roots as &$i) {
            if ($i === $root) {
                return $last;
            }
            $last = $i->id;
        }
        return $last;
    }

    /** Returns next thread root index
     * @param id INTEGER message number
     */
    public function nextThread($id)
    {
        $root =& $this->root($id);
        $ok   = false;
        foreach ($this->roots as &$i) {
            if ($ok) {
                return $i->id;
            }
            if ($i === $root) {
                $ok = true;
            }
        }
        return null;
    }

    /** Return prev post in the thread
     * @param id INTEGER message number
     */
    public function prevPost($id)
    {
        $parent =& $this->overview[$id]->parent;
        if (is_null($parent)) {
            return null;
        }
        $last = $parent->id;
        foreach ($parent->children as &$child) {
            if ($child->id == $id) {
                return $last;
            }
            $last = $child->id;
        }
        return null;
    }

    /** Return next post in the thread
     * @param id INTEGER message number
     */
    public function nextPost($id)
    {
        $cur =& $this->overview[$id];
        if (count($cur->children) != 0) {
            return $cur->children[0]->id;
        }

        $parent =& $cur;
        while (true) {
            $parent =& $cur->parent;
            if (is_null($parent)) {
                return null;
            }
            $ok = false;
            foreach ($parent->children as &$child) {
                if ($ok) {
                    return $child->id;
                }
                if ($child === $cur) {
                    $ok = true;
                }
            }
            $cur =& $parent;
        }
        return null;
    }

    /** Look for an unread message in the thread rooted by the message
     * @param id INTEGER message number
     */
    private function _nextUnread(BananaSpoolHead &$cur)
    {
        if (!$cur->isread) {
            return $cur->id;
        }
        foreach ($cur->children as &$child) {
            $unread = $this->_nextUnread($child);
            if (!is_null($unread)) {
                return $unread;
            }
        }
        return null;
    }

    /** Find next unread message
     * @param id INTEGER message number
     */
    public function nextUnread($id = null)
    {
        if (!$this->unreadnb) {
            return null;
        }

        if (!is_null($id)) {
            // Look in message children
            foreach ($this->overview[$id]->children as &$child) {
                $next = $this->_nextUnread($child);
                if (!is_null($next)) {
                    return $next;
                }
            }
        }

        // Look in current thread
        if (is_null($id)) {
            $cur = null;
        } else {
            $cur =& $this->overview[$id];
        }
        do {
            if (is_null($cur)) {
                $parent =& $cur;
                $ok     = true;
            } else {
                $parent =& $cur->parent;
                $ok     =  false;
            }
            if (!is_null($parent)) {
                $array =& $parent->children;
            } else {
                $array =& $this->roots;
            }
            foreach ($array as &$child) {
                if ($ok) {
                    $next = $this->_nextUnread($child);
                    if (!is_null($next)) {
                        return $next;
                    }
                }
                if ($child === $cur) {
                    $ok = true;
                }
            }
            $cur =& $parent;
        } while(!is_null($cur));
        return null;
    }
}
// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

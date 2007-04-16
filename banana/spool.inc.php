<?php
/********************************************************************************
* include/spool.inc.php : spool subroutines
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/banana.inc.php';

define('BANANA_SPOOL_VERSION', '0.4');

/** Class spoolhead
 *  class used in thread overviews
 */
class BananaSpoolHead
{
    /** date (timestamp) */
    public $date;
    /** subject */
    public $subject;
    /** author */
    public $from;
    /** reference of parent */
    public $parent = null;
    /** paren is direct */
    public $parent_direct;
    /** array of children */
    public $children = Array();
    /** true if post is read */
    public $isread;
    /** number of posts deeper in this branch of tree */
    public $desc;
    /**  same as desc, but counts only unread posts */
    public $descunread;

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
    public function __construct(array &$message)
    {
        $this->date       = $message['date'];
        $this->subject    = $message['subject'];
        $this->from       = $message['from'];
        $this->desc       = 1;
        $this->isread     = true;
        $this->descunread = 0;
    }
}


class BananaSpool
{
    private $version;
    private $mode;

    /**  group name */
    public $group;
    /**  spool */
    public $overview;
    /**  array msgid => msgnum */
    public $ids;
    /** thread starts */
    public $roots;

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

    private static function spoolFilename($group)
    {
        $file = Banana::$spool_root . '/' . Banana::$protocole->name() . '/';
        if (!is_dir($file)) {
            mkdir($file);
        }
        return $file . Banana::$protocole->filename();
    }

    private static function &readFromFile($group)
    {
        $spool = null;
        $file = BananaSpool::spoolFilename($group);
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

    private function compare($a, $b)
    {
        return ($b->date >= $a->date);
    }

    private function saveToFile()
    {
        $file = BananaSpool::spoolFilename($this->group);
        uasort($this->overview, array($this, 'compare'));

        $this->roots = Array();
        foreach($this->overview as $id=>$msg) {
            if (is_null($msg->parent)) {
                $this->roots[] = $id;
            }
        }

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
        if (is_array($this->overview)) {
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

        if (!is_array($this->ids)) {
            $this->ids = array();
        }
        foreach ($messages as $id=>&$message) {
            $this->ids[$message['message-id']] = $id;
        }

        if (!is_array($this->overview)) {
            $this->overview = array();
        }
        foreach ($messages as $id=>&$message) {
            if (!isset($this->overview[$id])) {
                $this->overview[$id] = new BananaSpoolHead($message);
            }
            $msg    =& $this->overview[$id];
            $msgrefs = BananaMessage::formatReferences($message);
            $parents = preg_grep('/^\d+$/', $msgrefs);
            $msg->parent = array_pop($parents);
            $msg->parent_direct = preg_match('/^\d+$/', array_pop($msgrefs));

            if (!is_null($p = $msg->parent)) {
                if (empty($this->overview[$p])) {
                    $this->overview[$p] = new BananaSpoolHead($messages[$p]);
                }
                $this->overview[$p]->children[] = $id;

                while (!is_null($p)) {
                    $this->overview[$p]->desc += $msg->desc;
                    if ($p != $this->overview[$p]->parent) {
                        $p = $this->overview[$p]->parent;
                    } else {
                        $p = null;
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

        if (!is_array($this->ids)) {
            $this->ids = array();
        }
        $newpostsids = array_intersect($newpostsids, array_keys($this->ids));
        foreach ($newpostsids as $mid) {
            $id = $this->ids[$mid];
            if ($this->overview[$id]->isread) {
                $this->overview[$id]->isread = false;
                $this->unreadnb++;
                while (isset($id)) {
                    $this->overview[$id]->descunread++;
                    $id = $this->overview[$id]->parent;
                }
            }
        }
    }

    public function setMode($mode)
    {
        $this->mode = $mode;
        switch ($mode) {
          case Banana::SPOOL_UNREAD:
            foreach ($this->roots as $k=>$i) {
                if ($this->overview[$i]->descunread == 0) {
                    $this->killdesc($i);
                    unset($this->roots[$k]);
                }
            }
            break;
        }
    }

    /** Mark the given id as read
     * @param id MSGNUM of post
     */
    public function markAsRead($id)
    {
        if (!$this->overview[$id]->isread) {
            $this->overview[$id]->isread = true;
            $this->unreadnb--;
            while (isset($id)) {
                $this->overview[$id]->descunread--;
                $id = $this->overview[$id]->parent;
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
        if (is_null($array) && is_array($this->roots)) {
            $array =& $this->roots;
        } elseif (is_null($array)) {
            return;
        }
        foreach ($array as $id) {
            if (!$this->overview[$id]->isread) {
                $this->markAsRead($id);
                if (!$this->unreadnb) {
                    return;
                }
            }
            if ($this->overview[$id]->descunread) {
                $this->markAllAsRead($this->overview[$id]->children);
            }
        }
    }

    /** kill post and childrens
     * @param $_id MSGNUM of post
     */
    private function killdesc($_id)
    {
        if (sizeof($this->overview[$_id]->children)) {
            foreach ($this->overview[$_id]->children as $c) {
                $this->killdesc($c);
            }
        }
        unset($this->overview[$_id]);
        if (($msgid = array_search($_id, $this->ids)) !== false) {
            unset($this->ids[$msgid]);
        }
    }

    /** delete a post from overview
     * @param $_id MSGNUM of post
     */
    public function delid($_id, $write = true)
    {
        if (isset($this->overview[$_id])) {
            $overview =& $this->overview[$_id];
            if (!$overview->isread) {
                $this->markAsRead($_id);
            }
            if ($overview->parent) {
                $p      =  $overview->parent;
                $parent =& $this->overview[$p];
                $parent->children = array_diff($parent->children, array($_id));
                if (sizeof($overview->children)) {
                    $parent->children = array_merge($parent->children, $overview->children);
                    foreach ($overview->children as $c) {
                        $this->overview[$c]->parent        = $p;
                        $this->overview[$c]->parent_direct = false;
                    }
                }
                while (isset($p)) {
                    $this->overview[$p]->desc--;
                    $p = $this->overview[$p]->parent;
                }
            } elseif ($overview->children) {
                foreach ($overview->children as $c) {
                    $this->overview[$c]->parent = null;
                }
            }
            unset($overview);
            unset($this->overview[$_id]);
            $msgid = array_search($_id, $this->ids);
            if ($msgid !== false) {
                unset($this->ids[$msgid]);
            }
            $msgid = array_search($_id, $this->roots);
            if ($msgid !== false) {
                unset($this->roots[$msgid]);
            }
            
            if ($write) {
                $this->saveToFile();
            }
        }
    }

    private function formatDate($stamp)
    {
        $today  = intval(time() / (24*3600));
        $dday   = intval($stamp / (24*3600));

        if ($today == $dday) {
            $format = "%H:%M";
        } elseif ($today == 1 + $dday) {
            $format = _b_('hier')." %H:%M";
        } elseif ($today < 7 + $dday) {
            $format = '%a %H:%M';
        } else {
            $format = '%a %e %b';
        }
        return strftime($format, $stamp);
    }

    /** displays children tree of a post
     * @param $_id INTEGER MSGNUM of post
     * @param $_index INTEGER linear number of post in the tree
     * @param $_first INTEGER linear number of first post displayed
     * @param $_last INTEGER linear number of last post displayed
     * @param $_ref STRING MSGNUM of current post 
     * @param $_pfx_node STRING prefix used for current node
     * @param $_pfx_end STRING prefix used for children of current node
     * @param $_head BOOLEAN true if first post in thread
     *
     * If you want to analyse subject, you can define the function hook_formatDisplayHeader
     */
    private function _to_html($_id, $_index, $_first=0, $_last=0, $_ref="", $_pfx_node="", $_pfx_end="", $_head=true, $_pfx_id="")
    {
        static $spfx_f, $spfx_n, $spfx_Tnd, $spfx_Lnd, $spfx_snd, $spfx_T, $spfx_L, $spfx_s, $spfx_e, $spfx_I;
        if (!isset($spfx_f)) {
            $spfx_f   = Banana::$page->makeImg(Array('img' => 'k1',       'alt' => 'o', 'height' => 21,  'width' => 9)); 
            $spfx_n   = Banana::$page->makeImg(Array('img' => 'k2',       'alt' => '*', 'height' => 21,  'width' => 9));
            $spfx_Tnd = Banana::$page->makeImg(Array('img' => 'T-direct', 'alt' => '+', 'height' => 21, 'width' => 12));
            $spfx_Lnd = Banana::$page->makeImg(Array('img' => 'L-direct', 'alt' => '`', 'height' => 21, 'width' => 12));
            $spfx_snd = Banana::$page->makeImg(Array('img' => 's-direct', 'alt' => '-', 'height' => 21, 'width' => 5));
            $spfx_T   = Banana::$page->makeImg(Array('img' => 'T',        'alt' => '+', 'height' => 21, 'width' => 12));
            $spfx_L   = Banana::$page->makeImg(Array('img' => 'L',        'alt' => '`', 'height' => 21, 'width' => 12));
            $spfx_s   = Banana::$page->makeImg(Array('img' => 's',        'alt' => '-', 'height' => 21, 'width' => 5));
            $spfx_e   = Banana::$page->makeImg(Array('img' => 'e',   'alt' => '&nbsp;', 'height' => 21, 'width' => 12));
            $spfx_I   = Banana::$page->makeImg(Array('img' => 'I',        'alt' => '|', 'height' => 21, 'width' => 12));
        }

        $overview =& $this->overview[$_id];
        if ($_index + $overview->desc < $_first || $_index > $_last) {
            return '';
        }

        $res = '';
        if ($_index >= $_first) {
            $hc = empty($overview->children);

            $res .= '<tr id="'.$_pfx_id.$_id.'" class="' . ($_index%2 ? 'pair' : 'impair') . ($overview->isread ? '' : ' new') . "\">\n";
            $res .= '<td class="date">' . $this->formatDate($overview->date) . " </td>\n";
            $res .= '<td class="subj' . ($_index == $_ref ? ' cur' : '') . '"><div class="tree">'
                . $_pfx_node .($hc ? ($_head ? $spfx_f : ($overview->parent_direct ? $spfx_s : $spfx_snd)) : $spfx_n)
                . '</div>';
            $subject = $overview->subject;
            if (function_exists('hook_formatDisplayHeader')) {
                list($subject, $link) = hook_formatDisplayHeader('subject', $subject, true);
            } else {
                $subject = banana_catchFormats(banana_htmlentities(stripslashes($subject)));
                $link = null;
            }
            if (empty($subject)) {
                $subject = _b_('(pas de sujet)');
            }
            if ($_index != $_ref) {
                $subject = Banana::$page->makeLink(Array('group' => $this->group, 'artid' => $_id,
                                                    'text'  => $subject, 'popup' => $subject));
            }
            $res .= '&nbsp;' . $subject . $link;
            $res .= "</td>\n<td class='from'>" . BananaMessage::formatFrom($overview->from) . "</td>\n</tr>";

            if ($hc) {
                return $res;
            }
        } 

        $_index ++;
        $children = $overview->children;
        while ($child = array_shift($children)) {
            $overview =& $this->overview[$child];
            if ($_index > $_last) {
                return $res;
            }
            if ($_index + $overview->desc >= $_first) {
                if (sizeof($children)) {
                    $res .= $this->_to_html($child, $_index, $_first, $_last, $_ref,
                            $_pfx_end . ($overview->parent_direct ? $spfx_T : $spfx_Tnd),
                            $_pfx_end . $spfx_I, false,$_id.'_');
                } else {
                    $res .= $this->_to_html($child, $_index, $_first, $_last, $_ref,
                            $_pfx_end . ($overview->parent_direct ? $spfx_L : $spfx_Lnd),
                            $_pfx_end . $spfx_e, false,$_id.'_');
                }
            }
            $_index += $overview->desc;
        }

        return $res;
    }

    /** Displays overview
     * @param $_first INTEGER MSGNUM of first post
     * @param $_last INTEGER MSGNUM of last post
     * @param $_ref STRING MSGNUM of current/selectionned post
     */
    public function toHtml($first = 0, $overview = false)
    {
        $res = Banana::$page->makeJs('jquery');
        $res .= Banana::$page->makeJs('spool_toggle');

        if (!$overview) {
            $_first = $first;
            $_last  = $first + Banana::$spool_tmax - 1;
            $_ref   = null;
        } else {
            $_ref   = $this->getNdx($first);
            $_last  = $_ref + Banana::$spool_tafter;
            $_first = $_ref - Banana::$spool_tbefore;
            if ($_first < 0) {
                $_last -= $_first;
            }
        }
        $index = 1;
        foreach ($this->roots as $id) {
            $res   .= $this->_to_html($id, $index, $_first, $_last, $_ref);
            $index += $this->overview[$id]->desc ;
            if ($index > $_last) {
                break;
            }
        }
        return $res;
    }

    /** computes linear post index
     * @param $_id INTEGER MSGNUM of post
     * @return INTEGER linear index of post
     */
    public function getNdX($_id)
    {
        $ndx    = 1;
        $id_cur = $_id;
        while (true) {
            $id_parent = $this->overview[$id_cur]->parent;
            if (is_null($id_parent)) break;
            $pos       = array_search($id_cur, $this->overview[$id_parent]->children);
        
            for ($i = 0; $i < $pos ; $i++) {
                $ndx += $this->overview[$this->overview[$id_parent]->children[$i]]->desc;
            }
            $ndx++; //noeud père

            $id_cur = $id_parent;
        }

        foreach ($this->roots as $i) {
            if ($i==$id_cur) {
                break;
            }
            $ndx += $this->overview[$i]->desc;
        }
        return $ndx;
    }

    /** Return root message of the given thread
     * @param id INTEGER id of a message
     */
    public function root($id)
    {
        $id_cur = $id;
        while (true) {
            $id_parent = $this->overview[$id_cur]->parent;
            if (is_null($id_parent)) break;
            $id_cur = $id_parent;
        }
        return $id_cur;
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
        $root = $this->root($id);
        $last = null;
        foreach ($this->roots as $i) {
            if ($i == $root) {
                return $last;
            }
            $last = $i;
        }
        return $last;
    }

    /** Returns next thread root index
     * @param id INTEGER message number
     */
    public function nextThread($id)
    {
        $root = $this->root($id);
        $ok   = false;
        foreach ($this->roots as $i) {
            if ($ok) {
                return $i;
            }
            if ($i == $root) {
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
        $parent = $this->overview[$id]->parent;
        if (is_null($parent)) {
            return null;
        }
        $last = $parent;
        foreach ($this->overview[$parent]->children as $child) {
            if ($child == $id) {
                return $last;
            }
            $last = $child;
        }
        return null;
    }

    /** Return next post in the thread
     * @param id INTEGER message number
     */
    public function nextPost($id)
    {
        if (count($this->overview[$id]->children) != 0) {
            return $this->overview[$id]->children[0];
        }
        
        $cur    = $id;
        while (true) {
            $parent = $this->overview[$cur]->parent;
            if (is_null($parent)) {
                return null;
            }
            $ok = false;
            foreach ($this->overview[$parent]->children as $child) {
                if ($ok) {
                    return $child;
                }
                if ($child == $cur) {
                    $ok = true;
                }
            }
            $cur = $parent;
        }
        return null;
    }

    /** Look for an unread message in the thread rooted by the message
     * @param id INTEGER message number
     */
    private function _nextUnread($id)
    {
        if (!$this->overview[$id]->isread) {
            return $id;
        }
        foreach ($this->overview[$id]->children as $child) {
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
            foreach ($this->overview[$id]->children as $child) {
                $next = $this->_nextUnread($child);
                if (!is_null($next)) {
                    return $next;
                }
            }
        }

        // Look in current thread
        $cur = $id;
        do {
            $parent = is_null($cur) ? null : $this->overview[$cur]->parent;
            $ok     = is_null($cur) ? true : false;
            if (!is_null($parent)) {
                $array = &$this->overview[$parent]->children;
            } else {
                $array = &$this->roots;
            }
            foreach ($array as $child) {
                if ($ok) {
                    $next = $this->_nextUnread($child);
                    if (!is_null($next)) {
                        return $next;
                    }
                }
                if ($child == $cur) {
                    $ok = true;
                }
            }
            $cur = $parent;
        } while(!is_null($cur));
        return null;
    }    
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

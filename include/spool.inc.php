<?php
/********************************************************************************
* include/spool.inc.php : spool subroutines
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

if(!function_exists('_file_put_contents')) {
    function file_put_contents($filename, $data) {
        $fp = fopen($filename, 'w');
        if(!$fp) {
            trigger_error('file_put_contents cannot write in file '.$filename, E_USER_ERROR);
            return;
        }
        fputs($fp, $data);
        fclose($fp);
    }
}

/** Class spoolhead
 *  class used in thread overviews
 */
class SpoolHead
{
    /** date (timestamp) */
    var $date;
    /** subject */
    var $subject;
    /** author */
    var $from;
    /** reference of parent */
    var $parent;
    /** paren is direct */
    var $parent_direct;
    /** array of children */
    var $children = Array();
    /** true if post is read */
    var $isread;
    /** number of posts deeper in this branch of tree */
    var $desc;
    /**  same as desc, but counts only unread posts */
    var $descunread;

    /** constructor
     * @param $_date INTEGER timestamp of post
     * @param $_subject STRING subject of post
     * @param $_from STRING author of post
     * @param $_desc INTEGER desc value (1 for a new post)
     * @param $_read BOOLEAN true if read
     * @param $_descunread INTEGER descunread value (0 for a new post)
     */

    function SpoolHead($_date, $_subject, $_from, $_desc=1, $_read=true, $_descunread=0)
    {
        $this->date       = $_date;
        $this->subject    = $_subject;
        $this->from       = $_from;
        $this->desc       = $_desc;
        $this->isread     = $_read;
        $this->descunread = $_descunread;
    }
}

/** Class spool
 * builds and updates spool 
 */

define("BANANA_SPOOL_VERSION", '0.2');

class spool
{
    var $version;
    /**  spool */
    var $overview;
    /**  group name */
    var $group;
    /**  array msgid => msgnum */
    var $ids;
    /** thread starts */
    var $roots;

    /** constructor
     * @param $_nntp RESOURCE NNTP socket filehandle
     * @param $_group STRING group name
     * @param $_display INTEGER 1 => all posts, 2 => only threads with new posts
     * @param $_since INTEGER time stamp (used for read/unread)
     */
    function spool(&$_nntp, $_group, $_display=0, $_since="")
    {
        global $news;
        $this->group = $_group;
        $groupinfo   = $_nntp->group($_group);
        if (!$groupinfo) { return ($this = null); }


        $this->_readFromFile();

        $do_save = false;
        $first   = max($groupinfo[2]-$news['maxspool'], $groupinfo[1]);
        $last    = $groupinfo[2];
        if ($this->version == BANANA_SPOOL_VERSION) {
            for ($id = min(array_keys($this->overview)); $id<$first; $id++) { 
                $this->delid($id, false);
                $do_save = true;
            }
            $first = max(array_keys($this->overview))+1;
        } else {
            unset($this->overview, $this->ids);
            $this->version = BANANA_SPOOL_VERSION;
        }

        if ($first<$last && $groupinfo[0]) {
            $do_save = true;
            $this->_updateSpool($_nntp, "$first-$last");
        }

        if ($do_save) { $this->_saveToFile(); }

        $this->_updateUnread($_nntp, $_since, $_display);
    }

    function _readFromFile()
    {
        $file = dirname(dirname(__FILE__))."/spool/spool-{$this->group}.dat";
        if (file_exists($file)) {
            $this = unserialize(file_get_contents($file));
        }
    }

    function _saveToFile()
    {
        $file = dirname(dirname(__FILE__))."/spool/spool-{$this->group}.dat";
        uasort($this->overview, "spoolcompare");

        $this->roots = Array();
        foreach($this->overview as $id=>$msg) {
            if (is_null($msg->parent)) {
                $this->roots[] = $id;
            }
        }
        
        file_put_contents($file, serialize($this));
    }

    function _updateSpool(&$_nntp, $arg)
    {
        $dates    = array_map(strtotime,    $_nntp->xhdr("Date",    $arg));
        $subjects = array_map(headerdecode, $_nntp->xhdr("Subject", $arg));
        $froms    = array_map(headerdecode, $_nntp->xhdr("From",    $arg));
        $msgids   = $_nntp->xhdr("Message-ID", $arg);
        $refs     = $_nntp->xhdr("References", $arg);

        if (is_array($this->ids)) {
            $this->ids = array_merge($this->ids, array_flip($msgids));
        } else {
            $this->ids = array_flip($msgids);
        }

        foreach ($msgids as $id=>$msgid) {
            $msg                = new spoolhead($dates[$id], $subjects[$id], $froms[$id]);
            $refs[$id]          = str_replace('><', '> <', $refs[$id]);
            $msgrefs            = preg_split("/[ \t]/", strtr($refs[$id], $this->ids));
            $parents            = preg_grep('/^\d+$/', $msgrefs);
            $msg->parent        = array_pop($parents);
            $msg->parent_direct = preg_match('/^\d+$/', array_pop($msgrefs));

            if (isset($this->overview[$id])) {
                $msg->desc     = $this->overview[$id]->desc;
                $msg->children = $this->overview[$id]->children;
            }
            $this->overview[$id] = $msg;

            if ($p = $msg->parent) {
                if (empty($this->overview[$p])) {
                    $this->overview[$p] = new spoolhead($dates[$p], $subjects[$p], $froms[$p], 1);
                }
                $this->overview[$p]->children[] = $id;

                while ($p) {
                    $this->overview[$p]->desc += $msg->desc;
                    $p = $this->overview[$p]->parent;
                }
            }
        }
    }

    function _updateUnread(&$nntp, $since, $mode) {
        if (empty($since)) { return; }

        if (is_array($newpostsids = $nntp->newnews($since, $this->group))) {
            $newpostsids = array_intersect($newpostsids, array_keys($this->ids));
            foreach ($newpostsids as $mid) {
                $this->overview[$this->ids[$mid]]->isread     = false;
                $this->overview[$this->ids[$mid]]->descunread = 1;
                $parentmid = $this->ids[$mid];
                while (isset($parentmid)) {
                    $this->overview[$parentmid]->descunread ++;
                    $parentmid = $this->overview[$parentmid]->parent;
                }
            }

            if (count($newpostsids)) {
                switch ($mode) {
                    case 1:
                        foreach ($this->roots as $k=>$i) {
                            if ($this->overview[$i]->descunread==0) {
                                $this->killdesc($i);
                                unset($this->roots[$k]);
                            }
                        }
                        break;
                }
            }
        }
    }

    /** kill post and childrens
     * @param $_id MSGNUM of post
     */

    function killdesc($_id)
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

    function delid($_id, $write=true)
    {
        if (isset($this->overview[$_id])) {
            if (sizeof($this->overview[$_id]->parent)) {
                $this->overview[$this->overview[$_id]->parent]->children = 
                    array_diff($this->overview[$this->overview[$_id]->parent]->children, array($_id));
                if (sizeof($this->overview[$_id]->children)) {
                    $this->overview[$this->overview[$_id]->parent]->children = 
                        array_merge($this->overview[$this->overview[$_id]->parent]->children, $this->overview[$_id]->children);
                    foreach ($this->overview[$_id]->children as $c) {
                        $this->overview[$c]->parent        = $this->overview[$_id]->parent;
                        $this->overview[$c]->parent_direct = false;
                    }
                }
                $p = $this->overview[$_id]->parent;
                while ($p) {
                    $this->overview[$p]->desc--;
                    $p = $this->overview[$p]->parent;
                }
            } elseif (sizeof($this->overview[$_id]->children)) {
                foreach ($this->overview[$_id]->children as $c) {
                    $this->overview[$c]->parent = null;
                }
            }
            unset($this->overview[$_id]);
            $msgid = array_search($_id, $this->ids);
            if ($msgid) {
                unset($this->ids[$msgid]);
            }
            
            if ($write) { $this->_saveToFile(); }
        }
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
     */

    function disp_desc($_id, $_index, $_first=0, $_last=0, $_ref="", $_pfx_node="", $_pfx_end="", $_head=true) {
        global $css;
        $spfx_f   = '<img src="img/k1.gif" height="21" width="9" alt="o" />'; 
        $spfx_n   = '<img src="img/k2.gif" height="21" width="9" alt="*" />'; 
        $spfx_Tnd = '<img src="img/T-direct.gif" height="21" width="12" alt="+" />';
        $spfx_Lnd = '<img src="img/L-direct.gif" height="21" width="12" alt="`" />';
        $spfx_snd = '<img src="img/s-direct.gif" height="21" width="5" alt="-" />';
        $spfx_T   = '<img src="img/T.gif" height="21" width="12" alt="+" />';
        $spfx_L   = '<img src="img/L.gif" height="21" width="12" alt="`" />';
        $spfx_s   = '<img src="img/s.gif" height="21" width="5" alt="-" />';
        $spfx_e   = '<img src="img/e.gif" height="21" width="12" alt="&nbsp;" />';
        $spfx_I   = '<img src="img/I.gif" height="21" width="12"alt="|" />';

        if ($_index + $this->overview[$_id]->desc < $_first || $_index > $_last) {
            return;
        }

        if ($_index>=$_first) {
            $us = ($_index == $_ref);
            $hc = empty($this->overview[$_id]->children);

            echo '<tr class="'.($_index%2?$css["pair"]:$css["impair"]).($this->overview[$_id]->isread?'':' new')."\">\n";
            echo "<td class=\"{$css['date']}\">".locale_header_date($this->overview[$_id]->date)." </td>\n";
            echo "<td class=\"{$css['subject']}\">"
                ."<div class=\"{$css['tree']}\">$_pfx_node".($hc?($_head?$spfx_f:($this->overview[$_id]->parent_direct?$spfx_s:$spfx_snd)):$spfx_n)
                ."</div>";
            if ($_index == $_ref) {
                echo '<span class="isref">'.htmlentities($this->overview[$_id]->subject).'</span>';
            } else {
                echo "<a href='article.php?group={$this->group}&amp;id=$_id'>".htmlentities($this->overview[$_id]->subject).'</a>';
            }
            echo "</td>\n<td class=\"{$css['author']}\">".formatFrom($this->overview[$_id]->from)."</td>\n</tr>";

            if ($hc) { return; }
        } 

        $_index ++;

        $children = $this->overview[$_id]->children;
        while ($child = array_shift($children)) {
            if ($_index > $_last) { return; }
            if ($_index+$this->overview[$child]->desc >= $_first) {
                if (sizeof($children)) {
                    $this->disp_desc($child, $_index, $_first, $_last, $_ref,
                            $_pfx_end.($this->overview[$child]->parent_direct?$spfx_T:$spfx_Tnd),
                            $_pfx_end.$spfx_I, false);
                } else {
                    $this->disp_desc($child, $_index, $_first, $_last, $_ref,
                            $_pfx_end.($this->overview[$child]->parent_direct?$spfx_L:$spfx_Lnd),
                            $_pfx_end.$spfx_e, false);
                }
            }
            $_index += $this->overview[$child]->desc;
        }
    }

    /** Displays overview
     * @param $_first INTEGER MSGNUM of first post
     * @param $_last INTEGER MSGNUM of last post
     * @param $_ref STRING MSGNUM of current/selectionned post
     */

    function disp($_first=0, $_last=0, $_ref="") {
        global $css;
        $index = 1;
        if (sizeof($this->overview)) {
            foreach ($this->roots as $id) {
                $this->disp_desc($id, $index, $_first, $_last, $_ref);
                $index += $this->overview[$id]->desc ;
                if ($index > $_last) { break; }
            }
        } else {
            echo "<tr class=\"{$css['pair']}\">\n";
            echo "\t<td colspan=\"3\">\n";
            echo "\t\tNo post in this newsgroup\n";
            echo "\t</td>\n";
            echo "</tr>\n";
        }
    }

    /** computes linear post index
     * @param $_id INTEGER MSGNUM of post
     * @return INTEGER linear index of post
     */

    function getndx($_id) {
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
}

?>

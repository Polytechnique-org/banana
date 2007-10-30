<?php
/********************************************************************************
* include/tree.inc.php : thread tree
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/


define('BANANA_TREE_VERSION', '0.1');

/**
 * Class representing a thread tree
 */
class BananaTree
{
    /** Tree cache
     */
    static private $cache = array();

    /** Tree format
     */
    public $version;

    /** Last update timestamp
     */
    public $time = 0;

    /** Data
     */
    public $data;

    /** Construct a new tree from a given root
     */
    public function __construct(BananaSpoolHead &$root)
    {
        if (empty($root->children)) {
            $this->data = null;
        } else {
            $tree =& $this->builder($root);
            $this->data = '<div class="tree"><div style="height:18px">'
                        . implode("</div>\n<div style=\"height:18px\">", $tree)
                        . '</div></div>';
        }
        $this->time = time();
        $this->version = BANANA_TREE_VERSION;
        $this->saveToFile($root->id);
    }

    private function &builder(BananaSpoolHead &$head)
    {
        static $t_e, $u_h, $u_ht, $u_vt, $u_l, $u_f, $r_h, $r_ht, $r_vt, $r_l, $r_f;
        if (!isset($spfx_f)) {
            $t_e   = Banana::$page->makeImg(Array('img' => 'e',  'alt' => '&nbsp;', 'height' => 18,  'width' => 14));
            $u_h   = Banana::$page->makeImg(Array('img' => 'h2', 'alt' => '-', 'height' => 18,  'width' => 14));
            $u_ht  = Banana::$page->makeImg(Array('img' => 'T2', 'alt' => '+', 'height' => 18, 'width' => 14));
            $u_vt  = Banana::$page->makeImg(Array('img' => 't2', 'alt' => '`', 'height' => 18, 'width' => 14));
            $u_l   = Banana::$page->makeImg(Array('img' => 'l2', 'alt' => '|', 'height' => 18, 'width' => 14));
            $u_f   = Banana::$page->makeImg(Array('img' => 'f2', 'alt' => 't', 'height' => 18, 'width' => 14));
            $r_h   = Banana::$page->makeImg(Array('img' => 'h2r', 'alt' => '-', 'height' => 18,  'width' => 14));
            $r_ht  = Banana::$page->makeImg(Array('img' => 'T2r', 'alt' => '+', 'height' => 18, 'width' => 14));
            $r_vt  = Banana::$page->makeImg(Array('img' => 't2r', 'alt' => '`', 'height' => 18, 'width' => 14));
            $r_l   = Banana::$page->makeImg(Array('img' => 'l2r', 'alt' => '|', 'height' => 18, 'width' => 14));
            $r_f   = Banana::$page->makeImg(Array('img' => 'f2r', 'alt' => 't', 'height' => 18, 'width' => 14));
        }
        $style = 'background-color:' . $head->color . '; text-decoration: none';
        $text = '<span style="' . $style . '" title="' . banana_entities($head->name . ', ' . Banana::$spool->formatDate($head))
              . '"><input type="radio" name="banana_tree" '
              . (Banana::$msgshow_javascript ? 'onchange="window.location=\'' .
                    banana_entities(Banana::$page->makeURL(array('group' => Banana::$spool->group, 'artid' => $head->id))) . '\'"'
                    : ' disabled="disabled"')
              . ' /></span>';
        $array = array($text);
        foreach ($head->children as $key=>&$msg) {
            $tree =& $this->builder($msg);
            $last = $key == count($head->children) - 1;
            foreach ($tree as $kt=>&$line) {
                if ($kt === 0 && $key === 0 && !$last) {
                    $array[0] .= ($msg->isread ? $r_ht : $u_ht) . $line;
                } else if($kt === 0 && $key === 0) {
                    $array[0] .= ($msg->isread ? $r_h : $u_h)  . $line;
                } else if ($kt === 0 && $last) {
                    $array[] = $t_e . ($msg->isread ? $r_vt : $u_vt) . $line;
                } else if ($kt === 0) {
                    $array[] = $t_e . ($msg->isread ? $r_f : $u_f) . $line;
                } else if ($last) {
                    $array[] = $t_e . $t_e . $line;
                } else {
                    $array[] = $t_e . ($msg->isread ? $r_l : $u_l) . $line;
                }
            }
            unset($tree);
        }
        return $array;
    }

    /** Save the content of the tree into a file
     */
    private function saveToFile($id)
    {
        file_put_contents(BananaTree::filename($id), serialize($this));
    }

    /** Get filename
     */
    static private function filename($id)
    {
        return BananaSpool::getPath('tree_' . $id);
    }

    /** Read a tree from a file
     */
    static private function &readFromFile($id)
    {
        $tree = null;
        $file = BananaTree::filename($id);
        if (!file_exists($file)) {
            return $tree;
        }
        $tree = unserialize(file_get_contents($file));
        if ($tree->version != BANANA_TREE_VERSION) {
            $tree = null;
        }
        return $tree;
    }

    /** Build a tree for the given id
     */
    static public function &build($id)
    {
        $root =& Banana::$spool->root($id);
        if (!isset(BananaTree::$cache[$root->id])) {
            $tree =& BananaTree::readFromFile($root->id);
            if (is_null($tree) || $tree->time < $root->time) {
                $tree = new BananaTree($root);
            }
            BananaTree::$cache[$root->id] =& $tree;
        }
        return BananaTree::$cache[$root->id];
    }

    /** Kill the file associated to the given id
     */
    static public function kill($id)
    {
        @unlink(BananaTree::filename($id));
    }
}
// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

<?php
/********************************************************************************
* include/tree.inc.php : thread tree
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/


define('BANANA_TREE_VERSION', '0.1.2');

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
    public $data = array();

    /** Data caching
     */
    private $urls = array();
    private $title = array();

    private $displaid = null;

    /** Construct a new tree from a given root
     */
    public function __construct(BananaSpoolHead &$root)
    {
        if (empty($root->children)) {
            $this->data = null;
        } else {
            $this->data =& $this->builder($root);
        }
        $this->time = time();
        $this->version = BANANA_TREE_VERSION;
        $this->saveToFile($root->id);
    }

    private function &builder(BananaSpoolHead &$head)
    {
        $array = array(array($head->id));
        $this->urls[$head->id]  = banana_entities(Banana::$page->makeURL(array('group' => Banana::$group,
                                                                               'artid' => $head->id)));
        $this->title[$head->id] = banana_entities($head->name . ', ' . Banana::$spool->formatDate($head));
        foreach ($head->children as $key=>&$msg) {
            $tree =& $this->builder($msg);
            $last = $key == count($head->children) - 1;
            foreach ($tree as $kt=>&$line) {
                if ($kt === 0 && $key === 0 && !$last) {
                    $array[0] = array_merge($array[0], array(array('+', $msg->id)), $line);
                } else if($kt === 0 && $key === 0) {
                    $array[0] = array_merge($array[0], array(array('-', $msg->id)), $line);
                } else if ($kt === 0 && $last) {
                    $array[] = array_merge(array(' ', array('`', $msg->id)), $line);
                } else if ($kt === 0) {
                    $array[] = array_merge(array(' ', array('t', $msg->id)), $line);
                } else if ($last) {
                    $array[] = array_merge(array(' ', ' '), $line);
                } else {
                    $array[] = array_merge(array(' ', array('|', $head->children[$key+1]->id)), $line);
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

    /** Return html to display the tree
     */
    public function &show()
    {
        if (!is_null($this->displaid) || is_null($this->data)) {
            return $this->displaid;
        }
        static $t_e, $tree;
        //$u_h, $u_ht, $u_vt, $u_l, $u_f, $r_h, $r_ht, $r_vt, $r_l, $r_f;
        if (!isset($t_e)) {
            $tree  = array();
            $t_e   = Banana::$page->makeImg(Array('img' => 'e',  'alt' => ' ', 'height' => 18, 'width' => 14));
            $tree['+'] = array(Banana::$page->makeImg(Array('img' => 'T2', 'alt' => '-', 'height' => 18,  'width' => 14)),
                               Banana::$page->makeImg(Array('img' => 'T2r', 'alt' => '+', 'height' => 18, 'width' => 14)));
            $tree['-'] = array(Banana::$page->makeImg(Array('img' => 'h2', 'alt' => '-', 'height' => 18,  'width' => 14)),
                               Banana::$page->makeImg(Array('img' => 'h2r', 'alt' => '-', 'height' => 18, 'width' => 14)));
            $tree['|'] = array(Banana::$page->makeImg(Array('img' => 'l2', 'alt' => '|', 'height' => 18, 'width' => 14)),
                               Banana::$page->makeImg(Array('img' => 'l2r', 'alt' => '|', 'height' => 18, 'width' => 14)));
            $tree['`'] = array(Banana::$page->makeImg(Array('img' => 't2', 'alt' => '`', 'height' => 18, 'width' => 14)),
                               Banana::$page->makeImg(Array('img' => 't2r', 'alt' => '`', 'height' => 18, 'width' => 14)));
            $tree['t'] = array(Banana::$page->makeImg(Array('img' => 'f2', 'alt' => 't', 'height' => 18, 'width' => 14)),
                               Banana::$page->makeImg(Array('img' => 'f2r', 'alt' => 't', 'height' => 18, 'width' => 14)));
        }
        $text = '<div class="tree">';
        foreach ($this->data as &$line) {
            $text .= '<div style="height: 18px">';
            foreach ($line as &$item) {
                if ($item == ' ') {
                    $text .= $t_e;
                } else if (is_array($item)) {
                    $head =& Banana::$spool->overview[$item[1]];
                    $text .= $tree[$item[0]][$head->isread ? 1 : 0];
                } else {
                    $head =& Banana::$spool->overview[$item];
                    $text .= '<span style="background-color: ' . $head->color . '; text-decoration: none" title="'
                          .  $this->title[$item] . '"><input type="radio" name="banana_tree" value="' . $head->id . '"';
                    if (Banana::$msgshow_javascript) {
                        $text .= ' onchange="window.location=\'' . $this->urls[$item] . '\'"';
                    } else {
                        $text .= ' disabled="disabled"';
                    }
                    if (Banana::$artid == $item) {
                        $text .= ' checked="checked"';
                    }
                    $text .= '/></span>';
                }
            }
            $text .= "</div>\n";
        }
        $text .= '</div>';
        $this->displaid =& $text;
        return $text;
    }

    /** Get filename
     */
    static private function filename($id)
    {
        static $host;
        if (!isset($host)) {
            $host = parse_url(Banana::$page->makeURL(array()), PHP_URL_HOST);
        }
        return BananaSpool::getPath('tree_' . $id . '_' . $host);
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

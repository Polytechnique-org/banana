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

    /** Create a reference to a tree image.
     */
    static private function makeTreeImg($img, $alt)
    {
        return Banana::$page->makeImg(Array('img' => $img, 'alt' => $alt, 'height' => 18, 'width' => 14));
    }

    /** Add an entry to the static tree association table.
     */
    static private function addTreeKind(array& $tree, $ascii, $img)
    {
        $tree[$ascii] = array(self::makeTreeImg($img . Banana::$tree_unread, $ascii),
                              self::makeTreeImg($img . Banana::$tree_read, $ascii));
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
            $t_e   = self::makeTreeImg('e', ' ');
            $tree  = array();
            self::addTreeKind($tree, '+', 'p2');
            self::addTreeKind($tree, '-', 'm2');
            self::addTreeKind($tree, '|', 'l2');
            self::addTreeKind($tree, '`', 'a2');
            self::addTreeKind($tree, 't', 't2');
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

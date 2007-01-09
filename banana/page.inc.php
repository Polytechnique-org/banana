<?php
/********************************************************************************
* banana/page.inc.php : class for group lists
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once 'smarty/libs/Smarty.class.php';

class BananaPage extends Smarty
{
    private $error = array();
    private $page  = null;

    private $pages   = array();
    private $actions = array();

    public function __construct()
    {
        $this->Smarty();

        $this->compile_check = Banana::$debug_smarty;
        $this->template_dir  = dirname(__FILE__) . '/templates/';
        $this->compile_dir   = dirname(dirname(__FILE__)) . '/spool/templates_c/';
        $this->register_prefilter('banana_trimwhitespace');
    
    }

    public function trig($message)
    {
        $this->error[] = $message;
    }

    public function kill($message)
    {
        $this->trig($message);
        return $this->run();
    }

    public function setPage($page)
    {
        $this->page = $page;
    }

    public function registerAction($action_code, array $pages = null)
    {
        $this->actions[] = array('text' => $action_code, 'pages' => $pages);
        return true;
    }

    public function registerPage($name, $text, $template = null)
    {
        $this->pages[$name] = array('text' => $text, 'template' => $template);
        return true;
    }

    public function run()
    {
        $this->registerPage('subscribe', _b_('Abonnements'), null);
        $this->registerPage('forums', _b_('Les forums'), null);
        if (!is_null(Banana::$group)) {
            $this->registerPage('thread', Banana::$group, null);
            if (!is_null(Banana::$artid)) {
                $this->registerPage('message', _b_('Message'), null);
                if ($this->page == 'cancel') {
                    $this->registerPage('cancel', _b_('Annulation'), null);
                } elseif ($this->page == 'new') {
                    $this->registerPage('new', _b_('Répondre'), null);
                }
            } elseif ($this->page == 'new') {
                $this->registerPage('new', _b_('Nouveau'), null);
            }
        }
        foreach ($this->actions as $key=>&$action) {
            if (!is_null($action['pages']) && !in_array($this->page, $action['pages'])) {
                unset($this->actions[$key]);
            }
        }
        $this->assign('group',     Banana::$group);
        $this->assign('artid',     Banana::$artid);
        $this->assign('part',      Banana::$part);
        $this->assign('first',     Banana::$first);
        $this->assign('action',    Banana::$action);
        $this->assign('profile',   Banana::$profile);
        $this->assign('spool',     Banana::$spool);
        $this->assign('protocole', Banana::$protocole);

        $this->assign('errors',    $this->error);
        $this->assign('page',      $this->page);
        $this->assign('pages',     $this->pages);
        $this->assign('actions',   $this->actions);

        $this->register_function('url',     array($this, 'makeUrl'));
        $this->register_function('link',    array($this, 'makeLink'));
        $this->register_function('imglink', array($this, 'makeImgLink'));
        $this->register_function('img',     array($this, 'makeImg'));
        if (!Banana::$debug_smarty) {
            $error_level = error_reporting(0);
        }
        $text = $this->fetch('banana-base.tpl');
        $text = banana_utf8entities($text);
        if (!Banana::$debug_smarty) {
            error_reporting($error_level);
        }
        return $text;
    }

    public function makeUrl($params, &$smarty = null)
    {
        if (function_exists('hook_makeLink')
                && $res = hook_makeLink($params)) {
            return $res;
        }   
        $proto = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
        $host  = $_SERVER['HTTP_HOST'];
        $file  = $_SERVER['PHP_SELF'];
    
        if (count($params) != 0) {
            $get = '?';
            foreach ($params as $key=>$value) {
                if (strlen($get) != 1) {
                    $get .= '&';
                }
                $get .= $key . '=' . $value;
            }
        } else {
            $get = '';
        }     
        return $proto . $host . $file . $get;
    }

    public function makeLink($params, &$smarty = null)
    {
        $catch = array('text', 'popup', 'class', 'accesskey');
        foreach ($catch as $key) {
            ${$key} = isset($params[$key]) ? $params[$key] : null;
            unset($params[$key]);
        }
        $link = $this->makeUrl($params, &$smarty);
        if (is_null($text)) {
            $text = $link;
        }
        if (!is_null($accesskey)) {
            $popup .= ' (raccourci : ' . $accesskey . ')';
        }
        if (!is_null($popup)) {
            $popup = ' title="' . $popup . '"';
        }
        if (!is_null($class)) {
            $class = ' class="' . $class . '"';
        }
        $target = null;
        if (isset($params['action']) && $params['action'] == 'view') {
            $target = ' target="_blank"';
        }
        if (!is_null($accesskey)) {
            $accesskey = ' accesskey="' . $accesskey . '"';
        }
        return '<a href="' . htmlentities($link) . '"'
              . $target . $popup . $class . $accesskey
              . '>' . $text . '</a>';
    }

    public function makeImg($params, &$smarty = null)
    {
        $catch = array('img', 'alt', 'height', 'width');
        foreach ($catch as $key) {
            ${$key} = isset($params[$key]) ? $params[$key] : null;
        }
        $img .= ".gif";
        if (function_exists('hook_makeImg')
                && $res = hook_makeImg($img, $alt, $height, $width)) {
            return $res;
        }

        if (!is_null($width)) {
            $width = ' width="' . $width . '"';
        }
        if (!is_null($height)) {
            $height = ' height="' . $height . '"';
        }

        $proto = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
        $host  = $_SERVER['HTTP_HOST'];
        $file  = dirname($_SERVER['PHP_SELF']) . '/img/' . $img;
        $url   = $proto . $host . $file;

        return '<img src="' . $url . '"' . $height . $width . ' alt="' . _b_($alt) . '" />';
    }

    public function makeImgLink($params, &$smarty = null)
    {
        $params['alt'] = _b_($params['alt']);
        $params['popup'] = $params['alt'];
        $params['text'] = $this->makeImg($params, $smarty);
        return $this->makeLink($params, $smarty);
    }

    /** Redirect to the page with the given parameter
     * @ref makeLink
     */
    public function redirect($params = array())
    {
        header('Location: ' . $this->makeUrl($params));
    }
}

// {{{  function banana_trimwhitespace

function banana_trimwhitespace($source, &$smarty)
{
    $tags = array('script', 'pre', 'textarea');

    foreach ($tags as $tag) {
        preg_match_all("!<{$tag}[^>]+>.*?</{$tag}>!is", $source, ${$tag});
        $source = preg_replace("!<{$tag}[^>]+>.*?</{$tag}>!is", "&&&{$tag}&&&", $source);
    }

    // remove all leading spaces, tabs and carriage returns NOT
    // preceeded by a php close tag.
    $source = preg_replace('/((?<!\?>)\n)[\s]+/m', '\1', $source);

    foreach ($tags as $tag) {
        $source = preg_replace("!&&&{$tag}&&&!e",  'array_shift(${$tag}[0])', $source);
    }

    return $source;
}

// }}}


// vim:set et sw=4 sts=4 ts=4:
?>

<?php
/********************************************************************************
* banana/page.inc.php : class for group lists
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

if (@!include_once('Smarty.class.php')) {
    require_once 'smarty/libs/Smarty.class.php';
}

class BananaPage extends Smarty
{
    protected $error = array();
    protected $page  = null;

    protected $pages   = array();
    protected $killed  = array();
    protected $actions = array();

    public $css = '';

    public function __construct()
    {
        parent::Smarty();

        $this->compile_check = Banana::$debug_smarty;
        $this->template_dir  = dirname(__FILE__) . '/templates/';
        $this->compile_dir   = Banana::$spool_root . '/templates_c/';
        $this->register_prefilter('banana_trimwhitespace');
        if (!is_dir($this->compile_dir)) {
            mkdir($this->compile_dir);
        }
    }

    /** Add an error message
     * @param message STRING html code of the error to display
     */
    public function trig($message)
    {
        $this->error[] = $message;
    }

    /** Kill the current page (generate an error message and skip page generation)
     * @param message STRING html code of the error message to display
     @ @return XHTML code of the page
     */
    public function kill($message)
    {
        $this->trig($message);
        $this->assign('killed', true);
        return $this->run();
    }

    /** Set the current page
     * @param page STRING page name
     */
    public function setPage($page)
    {
        $this->page = $page;
        return true;
    }

    /** Register an action to show on banana page
     * @param action_code HTML code of the action
     * @param pages ARRAY pages where to show the action (null == every pages)
     * @return true if success
     */
    public function registerAction($action_code, array $pages = null)
    {
        $this->actions[] = array('text' => $action_code, 'pages' => $pages);
        return true;
    }

    /** Register a new page
     * @param name Name of the page
     * @param text Text for the tab of the page
     * @param template Template path for the page if null, the page is not handled by banana
     * @return true if success
     */
    public function registerPage($name, $text, $template = null)
    {
        $this->pages[$name] = array('text' => $text, 'template' => $template);
        return true;
    }

    /** Remove a page
     * @param page STRING page name to kill
     */
    public function killPage($page)
    {
        $this->killed[] = $page;
    }

    /** Add Inline CSS to put in the page headers
     * @param css CSS code
     */
    public function addCssInline($css)
    {
        $this->css .= $css;
    }

    /** Preparte the page generation
     * @return template to use
     */
    protected function prepare()
    {
        $this->registerPage('subscribe', _b_('Abonnements'), null);
        $this->registerPage('forums', _b_('Les forums'), null);
        if (!is_null(Banana::$group)) {
            $this->registerPage('thread', Banana::$group, null);
            if (!is_null(Banana::$artid)) {
                if (Banana::$spool) {
                    $subject = Banana::$spool->overview[Banana::$artid]->subject;
                } else if (Banana::$message) {
                    $subject = Banana::$message->getHeaderValue('subject');
                } else {
                    $subject = _b_('Message');
                }
                if (strlen($subject) > 30) {
                    $subject = substr($subject, 0, 30) . '…';
                }
                $this->registerPage('message', $subject, null);
                if ($this->page == 'cancel') {
                    $this->registerPage('cancel', _b_('Annulation'), null);
                } elseif ($this->page == 'new') {
                    $this->registerPage('new', _b_('Répondre'), null);
                }
            } elseif ($this->page == 'new') {
                $this->registerPage('new', _b_('Nouveau'), null);
            }
        }
        foreach ($this->killed as $page) {
            unset($this->pages[$page]);
        }
        foreach ($this->actions as $key=>&$action) {
            if (!is_null($action['pages']) && !in_array($this->page, $action['pages'])) {
                unset($this->actions[$key]);
            }
        }

        return 'banana-base.tpl';
    }

    /** Generate XHTML code
     */
    public function run()
    {
        $tpl = $this->prepare();
        if (!isset($this->pages[$this->page])) {
            $this->trig(_b_('La page demandée n\'existe pas'));
            $this->actions = array();
            $this->page = null;
        }

        return $this->_run($tpl);
    }

    /** Generate feed XML code
     */
    public function feed()
    {
        @list($lg) = explode('_', Banana::$profile['locale']);
        $tpl = 'banana-feed-' . Banana::$feed_format . '.tpl';
        $this->assign('copyright', Banana::$feed_copyright);
        $this->assign('generator', Banana::$feed_generator);
        $this->assign('email',     Banana::$feed_email);
        $this->assign('title_prefix', Banana::$feed_namePrefix);
        $this->assign('language', $lg);
        $this->register_function('rss_date', 'rss_date');
        header('Content-Type: application/rss+xml; charset=utf-8');
        echo $this->_run($tpl, false);
        exit;
    }

    /** Code generation
     */
    private function _run($tpl, $ent = true)
    {
        $this->assign('group',     Banana::$group);
        $this->assign('artid',     Banana::$artid);
        $this->assign('part',      Banana::$part);
        $this->assign('first',     Banana::$first);
        $this->assign('action',    Banana::$action);
        $this->assign('profile',   Banana::$profile);
        $this->assign('spool',     Banana::$spool);
        $this->assign('protocole', Banana::$protocole);
        $this->assign('showboxlist', Banana::$spool_boxlist);
        $this->assign('showthread',  Banana::$msgshow_withthread);
        $this->assign('withtabs'   , Banana::$withtabs);
        $this->assign('feed_format', Banana::$feed_format);
        $this->assign('feed_active', Banana::$feed_active);
        $this->assign('with_javascript', Banana::$msgshow_javascript);

        $this->register_function('url',     array($this, 'makeUrl'));
        $this->register_function('link',    array($this, 'makeLink'));
        $this->register_function('imglink', array($this, 'makeImgLink'));
        $this->register_function('img',     array($this, 'makeImg'));
        $this->register_modifier('b',       '_b_');

        $this->assign('errors',    $this->error);
        $this->assign('page',      $this->page);
        $this->assign('pages',     $this->pages);
        $this->assign('actions',   $this->actions);
        $this->register_modifier('banana_utf8entities', 'banana_utf8entities');
        $this->register_modifier('banana_entities', 'banana_entities');

        if ($ent) {
            $this->default_modifiers = Array('@banana_entities');
        }

        if (!Banana::$debug_smarty) {
            $error_level = error_reporting(0);
        }
        $text = $this->fetch($tpl);
        if (!Banana::$debug_smarty) {
            error_reporting($error_level);
        }
        return $text;
    }

    /** Build a URL in Banana
     * @param params ARRAY location datas
     * @param smarty OBJECT Smarty instance associated (null if none)
     * @return URL of the page associated with the given parameters
     *
     * Usual parameters are :
     * - group : the box name
     * - artid : the current message id (index of message-id)
     * - part  : part id to show (may be a content-id, xface or a mime-type for text)
     * - first : first linear-index to show in spool view
     * - action: like subscribe, cancel, new
     * - all others params are allowed, but not parsed by the base implementation of banana
     *
     * smarty funciton : {url param1=... param2=...}
     */
    public function makeUrl(array $params, &$smarty = null)
    {
        if (function_exists('hook_makeLink')
                && $res = hook_makeLink($params)) {
            return $res;
        }
        $proto = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
        $host  = Banana::$baseurl ? Banana::$baseurl : $_SERVER['SERVER_NAME'];
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

    /** Build a link to a Banana page
     * @param params ARRAY location datas
     * @param smarty OBJECT Smarty instance associated (null if none)
     * @return Link to the page associated with the given parameters
     *
     * Support all @ref makeURL parameters, but catch the following:
     * - text     : if set, defined the text of the link (if not set, the URL is used
     * - popup    : title of the link (showed as a tooltip on most browsers)
     * - class    : specific style class for the markup
     * - accesskey: keyboard key to trigger the link
     * None of this parameters is needed
     *
     * Smarty function : {link param1=... param2=...}
     */
    public function makeLink(array $params, &$smarty = null)
    {
        $catch = array('text', 'popup', 'class', 'accesskey', 'style');
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
            $popup = ' title="' . banana_entities($popup) . '"';
        }
        if (!is_null($class)) {
            $class = ' class="' . $class . '"';
        }
        if (!is_null($style)) {
            $style = ' style="' . $style . '"';
        }
        if (!is_null($accesskey)) {
            $accesskey = ' accesskey="' . $accesskey . '"';
        }
        return '<a href="' . banana_entities($link) . '"'
              . $popup . $class . $style . $accesskey
              . '>' . $text . '</a>';
    }

    /** Build a link to one of the banana built-in images
     * @param params ARRAY image datas
     * @param smarty OBJECT Smarty instance associated (null if none)
     * @return Img tag
     *
     * Supported parameters are
     * - img : name of the image (without its extension)
     * - alt : alternative text
     * - height and width : dimensions of the images
     * img and alt are needed
     *
     * Smarty function: {img img=... alt=... [height=...] [width=...]}
     */
    public function makeImg(array $params, &$smarty = null)
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
        $host  = Banana::$baseurl ? Banana::$baseurl : $_SERVER['SERVER_NAME'];
        $file  = dirname($_SERVER['PHP_SELF']) . '/img/' . $img;
        $url   = $proto . $host . $file;

        return '<img src="' . $url . '"' . $height . $width . ' alt="' . _b_($alt) . '" />';
    }

    /** Build a link to one of the banana built-in javascript
     * @param src STRING javascript name
     * @return Javascript tag
     */
    public function makeJs($src)
    {
        if (!Banana::$msgshow_javascript) {
            return '';
        }
        if (function_exists('hook_makeJs')
                && $res = hook_makeJs($src)) {
            return $res;
        }

        $proto = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
        $host  = Banana::$baseurl ? Banana::$baseurl : $_SERVER['SERVER_NAME'];
        $file  = dirname($_SERVER['PHP_SELF']) . '/javascript/' . $src . '.js';
        $url   = $proto . $host . $file;

        return '<script type="text/javascript" src="' . $url . '"/></script>';
    }

    /** Build a link with an image as text
     * @param params ARRAY image and location data
     * @param smarty OBJECT Smarty instance associated (null if none)
     * @return an image within an link
     *
     * All @ref makeImg and @ref makeLink parameters are supported
     * if text is set, the text will be appended after the image in the link
     *
     * Smarty function : {imglink img=... alt=... [param1=...]}
     */
    public function makeImgLink(array $params, &$smarty = null)
    {
        if (!isset($params['popup'])) {
            $params['popup'] = @$params['alt'];
        }
        $img = $this->makeImg($params, $smarty);
        if (isset($params['text'])) {
            $img .= ' ' . $params['text'];
        }
        $params['text'] = $img;
        unset($params['alt']);
        unset($params['img']);
        unset($params['width']);
        unset($params['height']);
        return $this->makeLink($params, $smarty);
    }

    /** Redirect to the page with the given parameter
     * @ref makeURL
     */
    public function redirect(array $params = array())
    {
        header('Location: ' . $this->makeUrl($params));
        exit;
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
// {{{ function rss_date

function rss_date($t)
{
    return date('r', $t);
}

// }}}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

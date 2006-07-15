<?php
/********************************************************************************
 * include/misc.inc.php : Misc functions
 * -------------------------
 *
 * This file is part of the banana distribution
 * Copyright: See COPYING files that comes with this distribution
 ********************************************************************************/

/********************************************************************************
 *  MISC
 */

function _b_($str) { return utf8_decode(dgettext('banana', utf8_encode($str))); }

function to_entities($str) {
    require_once dirname(__FILE__).'/utf8.php';
    return utf8entities(htmlentities($str, ENT_NOQUOTES, 'UTF-8'));
}

function is_utf8($s) { return iconv('utf-8', 'utf-8', $s) == $s; }

function textFormat_translate($format)
{
    switch (strtolower($format)) {
        case 'plain':       return _b_('Texte brut');
        case 'richtext':    return _b_('Texte enrichi');
        case 'html':        return _b_('HTML');
        default:            return $format;
    }
}

/** Redirect to the page with the given parameter
 * @ref makeLink
 */
function redirectInBanana($params)
{
    header('Location: ' . makeLink($params));
}

/** Make a link using the given parameters
 * @param ARRAY params, the parameters with
 *       key => value
 * Known key are:
 *  - group       = group name
 *  - artid/first = article id the the group
 *  - subscribe   = to show the subscription page
 *  - action      = action to do (new, cancel, view)
 *  - part        = to show the given MIME part of the article
 *  - pj          = to get the given attachment
 *  - xface       = to make a link to an xface
 *
 * Can be overloaded by defining a hook_makeLink function
 */
function makeLink($params)
{
    if (function_exists('hook_makeLink')
            && $res = hook_makeLink($params)) {
        return $res;
    }
    $proto = empty($_SERVER['HTTPS']) ? 'http://' : 'https://';
    $host  = $_SERVER['HTTP_HOST'];
    $file  = $_SERVER['PHP_SELF'];

    if (isset($params['xface'])) {
        $file = dirname($file) . '/xface.php';
        $get  = 'face=' . urlencode(base64_encode($params['xface']));
    } else if (count($params) != 0) {
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

/** Format a link to be use in a link
 * @ref makeLink
 */
function makeHREF($params, $text = null, $popup = null, $class = null)
{
    $link = makeLink($params);
    if (is_null($text)) {
        $text = $link;
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
    return '<a href="' . htmlentities($link) .'"' . $target . $popup . $class . '>' . $text . '</a>';
}

/** Format tree images links
 * @param img STRING Image name (without extension)
 * @param alt STRING alternative string
 * @param width INT  to force width of the image (null if not defined)
 *
 * This function can be overloaded by defining hook_makeImg()
 */
function makeImg($img, $alt, $height = null, $width = null)
{
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

    return '<img src="' . $url . '"' . $height . $width . ' alt="' . $alt . '" />';
}

/** Make a link using an image
 */
function makeImgLink($params, $img, $alt, $height = null, $width = null, $class = null)
{
    return makeHREF($params,
                    makeImg($img, ' [' . $alt . ']', $height, $width),
                    $alt,
                    $class);
}

/********************************************************************************
 * HTML STUFF
 * Taken from php.net
 */

/**
 * @return string
 * @param string
 * @desc Strip forbidden tags and delegate tag-source check to removeEvilAttributes()
 */
function removeEvilTags($source)
{
    $allowedTags = '<h1><b><i><a><ul><li><pre><hr><blockquote><img><br><font><p><small><big><sup><sub><code><em>';
    $source = preg_replace('|</div>|i', '<br />', $source);
    $source = strip_tags($source, $allowedTags);
    return preg_replace('/<(.*?)>/ie', "'<'.removeEvilAttributes('\\1').'>'", $source);
}

/**
 * @return string
 * @param string
 * @desc Strip forbidden attributes from a tag
 */
function removeEvilAttributes($tagSource)
{
    $stripAttrib = 'javascript:|onclick|ondblclick|onmousedown|onmouseup|onmouseover|'.
                   'onmousemove|onmouseout|onkeypress|onkeydown|onkeyup';
    return stripslashes(preg_replace("/$stripAttrib/i", '', $tagSource));
}

/** Convert html to plain text
 */
function htmlToPlainText($res)
{
    $res = trim(html_entity_decode(strip_tags($res, '<div><br><p>')));
    $res = preg_replace("@</?(br|p|div)[^>]*>@i", "\n", $res);
    if (!is_utf8($res)) {
        $res = utf8_encode($res);
    }   
    return $res;
}

/** Match **, // and __ to format plain text
 */
function formatPlainText($text)
{
    $formatting = Array('\*' => 'strong',
                        '_' => 'u',
                        '/' => 'em');
    foreach ($formatting as $limit=>$mark) {
        $text = preg_replace('@(^|\W)' . $limit . '(\w+)' . $limit . '(\W|$)@'
                            ,'\1<' . $mark . '>\2</' . $mark . '>\3'
                            , $text);
    }
    return $text;
} 

/********************************************************************************
 * RICHTEXT STUFF
 */

/** Convert richtext to html
 */
function richtextToHtml($source)
{
    $tags = Array('bold' => 'b',
                  'italic' => 'i',
                  'smaller' => 'small',
                  'bigger' => 'big',
                  'underline' => 'u',
                  'subscript' => 'sub',
                  'superscript' => 'sup',
                  'excerpt' => 'blockquote',
                  'paragraph' => 'p',
                  'nl' => 'br'
            );
            
    // clean unsupported tags
    $protectedTags = '<signature><lt><comment><'.join('><', array_keys($tags)).'>';
    $source = strip_tags($source, $protectedTags);
    
    // convert richtext tags to html
    foreach (array_keys($tags) as $tag) {
        $source = preg_replace('@(</?)'.$tag.'([^>]*>)@i', '\1'.$tags[$tag].'\2', $source);
    }

    // some special cases
    $source = preg_replace('@<signature>@i', '<br>-- <br>', $source);
    $source = preg_replace('@</signature>@i', '', $source);
    $source = preg_replace('@<lt>@i', '&lt;', $source);
    $source = preg_replace('@<comment[^>]*>((?:[^<]|<(?!/comment>))*)</comment>@i', '<!-- \1 -->', $source);
    return removeEvilAttributes($source);
}

/********************************************************************************
 *  HEADER STUFF
 */

function _headerdecode($charset, $c, $str) {
    $s = ($c == 'Q' || $c == 'q') ? quoted_printable_decode($str) : base64_decode($str);
    $s = iconv($charset, 'iso-8859-15', $s);
    return str_replace('_', ' ', $s);
}
 
function headerDecode($value) {
    $val = preg_replace('/(=\?[^?]*\?[BQbq]\?[^?]*\?=) (=\?[^?]*\?[BQbq]\?[^?]*\?=)/', '\1\2', $value);
    return preg_replace('/=\?([^?]*)\?([BQbq])\?([^?]*)\?=/e', '_headerdecode("\1", "\2", "\3")', $val);
}

function headerEncode($value, $trim = 0) {
    if ($trim) {
        if (strlen($value) > $trim) {
            $value = substr($value, 0, $trim) . "[...]";
        }
    }
    return "=?UTF-8?B?".base64_encode($value)."?=";
}

function header_translate($hdr) {
    switch ($hdr) {
        case 'from':            return _b_('De');
        case 'subject':         return _b_('Sujet');
        case 'newsgroups':      return _b_('Forums');
        case 'followup-to':     return _b_('Suivi-à');
        case 'date':            return _b_('Date');
        case 'organization':    return _b_('Organisation');
        case 'references':      return _b_('Références');
        case 'x-face':          return _b_('Image');
        default:
            if (function_exists('hook_headerTranslate')
                    && $res = hook_headerTranslate($hdr)) {
                return $res;
            }
            return $hdr;
    }
}

function formatDisplayHeader($_header,$_text) {
    global $banana;
    if (function_exists('hook_formatDisplayHeader')
            && $res = hook_formatDisplayHeader($_header, $_text)) {
        return $res;
    }

    switch ($_header) {
        case "date": 
            return formatDate($_text);
        
        case "followup-to":
            case "newsgroups":
            $res = "";
            $groups = preg_split("/[\t ]*,[\t ]*/",$_text);
            foreach ($groups as $g) {
                $res .= makeHREF(Array('group' => $g), $g) . ', ';
            }
            return substr($res,0, -2);

        case "from":
            return formatFrom($_text);
        
        case "references":
            $rsl     = "";
            $ndx     = 1;
            $text    = str_replace("><","> <",$_text);
            $text    = preg_split("/[ \t]/",strtr($text,$banana->spool->ids));
            $parents = preg_grep("/^\d+$/",$text);
            $p       = array_pop($parents);
            $par_ok  = Array();
            
            while ($p) {
                $par_ok[]=$p;
                $p = $banana->spool->overview[$p]->parent;
            }
            foreach (array_reverse($par_ok) as $p) {
                $rsl .= makeHREF(Array('group' => $banana->spool->group,
                                       'artid' => $p),
                                 $ndx) . ' ';
                $ndx++;
            }
            return $rsl;

        case "x-face":
            return '<img src="' . makeLink(Array('xface' => headerDecode($_text))) .'"  alt="x-face" />';
    
        case "subject":
            $link = null;
            if (function_exists('hook_getSubject')) {
                $link = hook_getSubject($_text);
            }
            return formatPlainText($_text) . $link;

        default:
            return htmlentities($_text);
    }
}

/********************************************************************************
 *  FORMATTING STUFF
 */

function formatDate($_text) {
    return strftime("%A %d %B %Y, %H:%M (fuseau serveur)", strtotime($_text));
}

function fancyDate($stamp) {
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

function formatFrom($text) {
#     From: mark@cbosgd.ATT.COM
#     From: mark@cbosgd.ATT.COM (Mark Horton)
#     From: Mark Horton <mark@cbosgd.ATT.COM>
    $mailto = '<a href="&#109;&#97;&#105;&#108;&#116;&#111;&#58;';

    $result = htmlentities($text);
    if (preg_match("/^([^ ]+)@([^ ]+)$/",$text,$regs)) {
        $result="$mailto{$regs[1]}&#64;{$regs[2]}\">".htmlentities($regs[1]."&#64;".$regs[2])."</a>";
    }
    if (preg_match("/^([^ ]+)@([^ ]+) \((.*)\)$/",$text,$regs)) {
        $result="$mailto{$regs[1]}&#64;{$regs[2]}\">".htmlentities($regs[3])."</a>";
    }
    if (preg_match("/^\"?([^<>\"]+)\"? +<(.+)@(.+)>$/",$text,$regs)) {
        $result="$mailto{$regs[2]}&#64;{$regs[3]}\">".htmlentities($regs[1])."</a>";
    }
    return preg_replace("/\\\(\(|\))/","\\1",$result);
}

function displayShortcuts($first = -1)
{
    global $banana;
    extract($banana->state);
    
    $res  = '<div class="banana_scuts">';
    $res .= '<span class="title"> Profil :</span> ' . makeHREF(Array('subscribe' => 1), _b_('Abonnements'));
    if (function_exists('hook_shortcuts') && $cstm = hook_shortcuts()) {
        $res .= ' . ' . $cstm;
    }
    $res .= '<br />';
    
    $res .=  '<span class="title">Navigation :</span> '
         . (is_null($group) ? 'Les forums' : makeHREF(Array(), _b_('Les forums')));

    if (!is_null($group)) {
        $res .= ' > ' . makeHREF(Array('group' => $group), $group);
        if (is_null($artid)) {
            if (sizeof($banana->spool->overview)>$banana->tmax) {
                $res .= ' > Pages<br />';
                $n = intval(log(count($banana->spool->overview), 10))+1;
                $i = 1;
                for ($ndx = 1 ; $ndx <= sizeof($banana->spool->overview) ; $ndx += $banana->tmax) {
                    if ($first==$ndx) {
                        $fmt = $i . ' ';
                    } else {
                        $fmt = makeHREF(Array('group' => $group,
                                              'first' => $ndx),
                                        $i, 
                                        '%0' . $n . 'u-%0' . $n . 'u')
                             . ' ';
                    }
                    $i++;
                    $res .= sprintf($fmt, $ndx, min($ndx+$banana->tmax-1,sizeof($banana->spool->overview)));
                }
            }
            if (!is_null($action)) {
                if ($action == 'new') {
                    $res .= ' > Nouveau Message';
                }
            }
        } else {
            if (!is_null($action)) {
                $res .= ' > ' . makeHREF(Array('group' => $group,
                                               'artid' => $artid),
                                         'Message')
                     . ' > ';
                if ($action == 'new') {
                    $res .= 'Répondre';
                } elseif ($action == 'cancel') {
                    $res .= 'Annuler';
                }
            } else {
                $res .= ' > Message';
            }
        }
    }
    return $res.'</div>';
}

/********************************************************************************
 *  FORMATTING STUFF : BODY
 */

function autoformat($text, $part = false)
{
    global $banana;
    $length = $banana->wrap;
    $all = null;
    if (!$part) {
        $all = ', all=1';
    }
    
    $cmd = "echo ".escapeshellarg($text)." | perl -MText::Autoformat -e 'autoformat {left=>1, right=>$length$all };'";
    exec($cmd, $result, $ret);
    if ($ret != 0) {
        $result = split("\n", $text);
    }
    return $result;
}                                

function wrap($text, $_prefix="", $_force=false)
{
    $parts = preg_split("/\n-- ?\n/", $text);
    if (count($parts)  >1) {
        $sign = "\n-- \n" . array_pop($parts);
        $text = join("\n-- \n", $parts);
    } else {
        $sign = '';
    }
   
    global $banana;
    $url    = $banana->url_regexp;
    $length = $banana->wrap;
    $max    = $length + ($length/10);
    $splits = split("\n", $text);
    $result = array();
    $next   = array();
    $format = false;
    foreach ($splits as $line) {
        if ($_force || strlen($line) > $max) {
            if (preg_match("!^(.*)($url)(.*)!i", $line, $matches)
                    && strlen($matches[2]) > $length && strlen($matches) < 900) {
                if (strlen($matches[1]) != 0) {
                    array_push($next, rtrim($matches[1]));
                    if (strlen($matches[1]) > $max) {
                        $format = true;
                    }
                }
                    
                if ($format) {
                    $result = array_merge($result, autoformat(join("\n", $next)));
                } else {
                    $result = array_merge($result, $next);
                }
                $format = false;
                $next   = array();
                array_push($result, $matches[2]);
                    
                if (strlen($matches[6]) != 0) {
                    array_push($next, ltrim($matches[6]));
                    if (strlen($matches[6]) > $max) {
                        $format = true;
                    }
                }
            } else {
                if (strlen($line) > 2 * $max) {
                    $next = array_merge($next, autoformat($line, true));
                } else {
                    $format = true;
                    array_push($next, $line);
                }
            }
        } else {
            array_push($next, $line);
        }
    }
    if ($format) {
        $result = array_merge($result, autoformat(join("\n", $next)));
    } else {
        $result = array_merge($result, $next);
    }

    return $_prefix.join("\n$_prefix", $result).($_prefix ? '' : $sign);
}

function cutlink($link)
{
    global $banana;
    
    if (strlen($link) > $banana->wrap) {
        $link = substr($link, 0, $banana->wrap - 3)."...";
    }
    return $link;
}

function cleanurl($url)
{
    $url = str_replace('@', '%40', $url);
    return '<a href="'.$url.'" title="'.$url.'">'.cutlink($url).'</a>';
}

function formatbody($_text, $format='plain', $flowed=false)
{
    if ($format == 'html') {
        $res = '<br/>'.html_entity_decode(to_entities(removeEvilTags($_text))).'<br/>';
    } else if ($format == 'richtext') {
        $res = '<br/>'.html_entity_decode(to_entities(richtextToHtml($_text))).'<br/>';
    } else {
        $res  = "\n" . to_entities(wrap($_text, "", $flowed)) . "\n";
        $res  = formatPlainText($res);
    }

    if ($format != 'html') {
        global $banana;
        $url  = $banana->url_regexp;
        $res  = preg_replace("/(&lt;|&gt;|&quot;)/", " \\1 ", $res);
        $res  = preg_replace("!$url!ie", "'\\1'.cleanurl('\\2').'\\3'", $res);
        $res  = preg_replace('/(["\[])?(?:mailto:)?([a-z0-9.\-+_]+@[a-z0-9.\-+_]+)(["\]])?/i', '\1<a href="mailto:\2">\2</a>\3', $res);
        $res  = preg_replace("/ (&lt;|&gt;|&quot;) /", "\\1", $res);

        if ($format == 'richtext') {
            $format = 'html';
        }
    }
 
    if ($format == 'html') {
        $res = preg_replace("@(</p>)\n?-- ?\n?(<p[^>]*>|<br[^>]*>)@", "\\1<br/>-- \\2", $res);
        $res = preg_replace("@<br[^>]*>\n?-- ?\n?(<p[^>]*>)@", "<br/>-- <br/>\\2", $res);
        $res = preg_replace("@(<pre[^>]*>)\n?-- ?\n@", "<br/>-- <br/>\\1", $res);
        $parts = preg_split("@(:?<p[^>]*>\n?-- ?\n?</p>|<br[^>]*>\n?-- ?\n?<br[^>]*>)@", $res);
        $sign  = '<hr style="width: 100%; margin: 1em 0em; " />';
        $end   = '<br />';
    } else {
        while (preg_match("@(^|<pre>|\n)&gt;@i", $res)) {
            $res  = preg_replace("@(^|<pre>|\n)((&gt;[^\n]*\n)+)@ie",
                "'\\1</pre><blockquote><pre>'"
                .".stripslashes(preg_replace('@(^|<pre>|\n)&gt;[ \\t\\r]*@i', '\\1', '\\2'))"
                .".'</pre></blockquote><pre>'",
                $res);
        }
        $res = preg_replace("@<pre>-- ?\n@", "<pre>\n-- \n", $res);
        $parts = preg_split("/\n-- ?\n/", $res);
        $sign  = '</pre><hr style="width: 100%; margin: 1em 0em; " /><pre>';
        $end   = null;
    }

    return join($sign, $parts) . $end;
}

// vim:set et sw=4 sts=4 ts=4
?>

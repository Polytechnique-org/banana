<?php
/********************************************************************************
 * * banana/message.func.inc.php : function to display messages
 * * ------------------------
 * *
 * * This file is part of the banana distribution
 * * Copyright: See COPYING files that comes with this distribution
 * ********************************************************************************/

require_once dirname(__FILE__) . '/mimepart.inc.php';
require_once dirname(__FILE__) . '/banana.inc.php';

// {{{ Plain Text Functions

function banana_isFlowed($line)
{
    return ctype_space(substr($line, -1)) && $line != '-- ';
}

function banana_removeQuotes($line, &$quote_level, $strict = true)
{
    $quote_level = 0;
    if (empty($line)) {
        return '';
    }
    while ($line{0} == '>') {
        $line = substr($line, 1);
        if (!$strict && ctype_space($line{0})) {
            $line = substr($line, 1);
        }
        $quote_level++;
    }
    if (ctype_space($line{0})) {
        $line = substr($line, 1);
    }
    return $line;
}

function banana_quote($line, $level, $mark = '>')
{
    $lines = explode("\n", $line);
    $quote = str_repeat($mark, $level);
    foreach ($lines as &$line) {
        $line = $quote . $line;
    }
    return implode("\n", $lines);
}

function banana_unflowed($text)
{
    $lines = explode("\n", $text);
    $text = '';
    while (!is_null($line = array_shift($lines))) {
        $level = 0;
        $line = banana_removeQuotes($line, $level);
        while (banana_isFlowed($line)) {
            $lvl = 0;
            if (empty($lines)) {
                break;
            }
            $nl  = $lines[0];
            $nl = banana_removeQuotes($nl, $lvl);
            if ($lvl == $level) {
                $line .= $nl;
                array_shift($lines);
            } else {
                break;
            }
        }
        $text .= banana_quote($line, $level) . "\n";
    }
    return $text;
}

function banana_wordwrap($text, $quote_level = 0)
{
    if ($quote_level > 0) {
        $length = Banana::$msgshow_wrap - $quote_level - 1;
        return banana_quote(wordwrap($text, $length), $quote_level);
    
    }
    return wordwrap($text, Banana::$msgshow_wrap);
}

function banana_catchFormats($text)
{
    $formatting = Array('em' => array('\B\/\b', '\b\/\B'),
                        'u' =>  array('\b_', '_\b'),
                        'strong' => array('\B\*\b', '\b\*\B'));
    $url = Banana::$msgshow_url;
    preg_match_all("/$url/ui", $text, $urls);
    $text = str_replace($urls[0], "&&&urls&&&", $text);
    foreach ($formatting as $mark=>$limit) {
        list($ll, $lr) = $limit;
        $text = preg_replace('/' . $ll . '(\w+?)' . $lr . '/us',
                             "<$mark>\\1</$mark>", $text);
    }
    return preg_replace('/&&&urls&&&/e', 'array_shift($urls[0])', $text);
}

// {{{ URL Catcher tools

function banana__cutlink($link)
{
    $link = banana_html_entity_decode($link, ENT_QUOTES);
    if (strlen($link) > Banana::$msgshow_wrap) {
        $link = substr($link, 0, Banana::$msgshow_wrap - 3) . "...";
    }
    return banana_htmlentities($link, ENT_QUOTES);
}

function banana__cleanURL($url)
{
    $url = str_replace('@', '%40', $url);
    if (strpos($url, '://') === false) {
        $url = 'http://' . $url;
    }
    return '<a href="'.$url.'" title="'.$url.'">' . banana__cutlink($url) . '</a>';
}

function banana__catchMailLink($email)
{
    $mid = '<' . $email . '>';
    if (isset(Banana::$spool->ids[$mid])) {
        return Banana::$page->makeLink(Array('group' => Banana::$group,
                                             'artid' => Banana::$spool->ids[$mid],
                                             'text'  => $email));
    } elseif (strpos($email, '$') !== false) {
        return $email;
    }
    return '<a href="mailto:' . $email . '">' . $email . '</a>';
}

// }}}

function banana_catchURLs($text)
{
    $url  = Banana::$msgshow_url;

    $res  = preg_replace("/&(lt|gt|quot);/", " &\\1; ", $text);
    $res  = preg_replace("/$url/uie", "'\\1'.banana__cleanurl('\\2').'\\3'", $res);
    $res  = preg_replace('/(["\[])?(?:mailto:|news:)?([a-z0-9.\-+_\$]+@([\-.+_]?[a-z0-9])+)(["\]])?/ie',
                         "'\\1' . banana__catchMailLink('\\2') . '\\4'",
                          $res);
    $res  = preg_replace("/ &(lt|gt|quot); /", "&\\1;", $res);
    return $res;
}

// {{{ Quotes catcher functions

function banana__replaceQuotes($text, $regexp)
{
    return stripslashes(preg_replace("@(^|<pre>|\n)$regexp@i", '\1', $text));
}

// }}}

function banana_catchQuotes($res, $strict = true)
{
    if ($strict) {
        $regexp = "&gt;";
    } else {
        $regexp = "&gt; *";
    }
    while (preg_match("/(^|<pre>|\n)$regexp/i", $res)) {
        $res  = preg_replace("/(^|<pre>|\n)(($regexp.*(?:\n|$))+)/ie",
            "'\\1</pre><blockquote><pre>'"
            ." . banana__replaceQuotes('\\2', '$regexp')"
            ." . '</pre></blockquote><pre>'",
            $res);
    }
    return $res;
}

function banana_catchSignature($res)
{
    $res = preg_replace("@<pre>-- ?\n@", "<pre>\n-- \n", $res);
    $parts = preg_split("/\n-- ?\n/", $res);
    $sign  = '</pre><hr style="width: 100%; margin: 1em 0em; " /><pre>';
    return join($sign, $parts);
}

function banana_plainTextToHtml($text, $strict = true)
{
    $text = banana_htmlentities($text);
    $text = banana_catchFormats($text);
    $text = banana_catchURLs($text);
    $text = banana_catchQuotes($text, $strict);
    $text = banana_catchSignature($text);
    return '<pre>' . $text . '</pre>';
}

function banana_wrap($text, $base_level = 0, $strict = true)
{
    $lines  = explode("\n", $text);
    $text   = '';
    $buffer = array();
    $level  = 0;
    while (!is_null($line = array_shift($lines))) {
        $lvl = 0;
        $line = banana_removeQuotes($line, $lvl, $strict);
        if($lvl != $level) {
            if (!empty($buffer)) {
                $text  .= banana_wordwrap(implode("\n", $buffer), $level + $base_level) . "\n";
                $buffer = array();
            }    
            $level  = $lvl;
        }
        $buffer[] = $line;
    }
    if (!empty($buffer)) {
        $text .= banana_wordwrap(implode("\n", $buffer), $level + $base_level);
    }
    return $text;
}

function banana_formatPlainText(BananaMimePart &$part, $base_level = 0)
{
    $text = $part->getText();
    if ($part->isFlowed()) {
        $text = banana_unflowed($text);
    }
    $text = banana_wrap($text, $base_level, $part->isFlowed());
    return banana_plainTextToHtml($text, $part->isFlowed());
}

function banana_quotePlainText(BananaMimePart &$part)
{
    $text = $part->getText();
    if ($part->isFlowed()) {
        $text = banana_unflowed($text);
    }
    return banana_wrap($text, 1);
}

// }}}
// {{{ HTML Functions

function banana_htmlentities($text, $quote = ENT_COMPAT)
{
    return htmlentities($text, $quote, 'UTF-8');
}

function banana_html_entity_decode($text, $quote = ENT_COMPAT)
{
    return html_entity_decode($text, $quote, 'UTF-8');
}

function banana_removeEvilAttributes($tagSource)
{
    $stripAttrib = 'javascript:|onclick|ondblclick|onmousedown|onmouseup|onmouseover|'.
                   'onmousemove|onmouseout|onkeypress|onkeydown|onkeyup';
    return stripslashes(preg_replace("/$stripAttrib/i", '', $tagSource));
}

function banana_cleanStyles($tag, $attributes)
{
    static $td_style, $conv, $size_conv;
    if (!isset($td_style)) {
        $conv = array('style' => 'style', 'width' => 'width', 'height' => 'height', 'border' => 'border-size',
                      'size' => 'font-size', 'align' => 'text-align', 'valign' => 'vertical-align', 'face' => 'font',
                      'bgcolor' => 'background-color', 'color' => 'color', 'style' => 'style',
                      'cellpadding' => 'padding', 'cellspacing' => 'border-spacing');
        $size_conv = array(1 => 'xx-small', 2 => 'x-small', 3 => 'small', 4 => 'medium', 5 => 'large',
                           6 => 'x-large',  7 => 'xx-large',
                           '-2' => 'xx-small', '-1' => 'x-small', '+1' => 'medium', '+2' => 'large',
                           '+3' => 'x-large', '+4' => 'xx-large');
        $td_style = array();
    }
    if ($tag == 'table') {
        array_unshift($td_style, '');
    }
    if ($tag == '/table') {
        array_shift($td_style);
    }
    if ($tag{0} == '/') {
        return '';
    }
    if ($tag == 'td') {
        $style = $td_style[0];
    } else {
        $style = '';
    }
    $attributes = str_replace("\n", ' ', stripslashes($attributes));
    $attributes = str_replace('= "', '="', $attributes);
    foreach ($conv as $att=>$stl) {
        $pattern = '/\b' . preg_quote($att, '/') . '=([\'"])?(.+?)(?(1)\1|(?:$| ))/i';
        if (preg_match($pattern, $attributes, $matches)) {
            $attributes = preg_replace($pattern, '', $attributes);
            $val = $matches[2];
            if ($att == 'cellspacing' && strpos($style, 'border-collapse') === false) {
                $style .= "border-collapse: separate; border-spacing: $val $val; ";
            } elseif ($att == 'cellpadding' && $tag == 'table') {
                $td_style[0] = "$stl: {$val}px; ";
            } elseif ($att == 'style') {
                $val = rtrim($val, ' ;');
                $style .= "$val; ";
            } elseif ($att == 'size') {
                $val = $size_conv[$val];
                $style .= "$stl: $val; ";
            } elseif (is_numeric($val)) {
                $style .= "$stl: {$val}px; ";
            } else {
                $style .= "$stl: $val; ";
            }
        }
    }
    if (!empty($style)) {
        $style = 'style="' . $style . '" ';
    }
    return ' ' . $style . trim($attributes);
}

function banana__filterCss($text)
{
    $text = preg_replace("/(,[\s\n\r]*)/s", '\1 .banana .message .body .html ', $text);
    return '.banana .message .body .html ' . $text;
}

function banana_filterCss($css)
{
    preg_match_all("/(^|\n|,\s*)\s*([\#\.@\w][^;\{\}\<]*?[\{])/s", $css, $matches);
    $css = preg_replace("/(^|\n)\s*([\#\.@\w][^;\{\}\<]*?)([\{])/se", '"\1" . banana__filterCss("\2") . "\3"', $css);
    $css = preg_replace('/ body\b/i', '', $css);
    if (!Banana::$msgshow_externalimages) {
        if (preg_match('!url\([^:\)]+:(//|\\\).*?\)!i', $css)) {
            $css = preg_replace('!url\([^:\)]+:(//|\\\).*?\)!i', 'url(invalid-image.png)', $css);
            Banana::$msgshow_hasextimages = true;
        }
    }
    return $css;
}
    
/**
 * @return string
 * @param string
 * @desc Strip forbidden tags and delegate tag-source check to removeEvilAttributes()
 */
function banana_cleanHtml($source, $to_xhtml = false)
{
    if (function_exists('tidy_repair_string')) {
        $tidy_config = array('drop-empty-paras' => true,
                             'drop-proprietary-attributes' => true,
                             'hide-comments' => true,
                             'logical-emphasis' => true, 
                             'output-xhtml' => true,
                             'replace-color' => true,
                             'join-classes'  => false,
                             'clean' => false,
                             'show-body-only' => false,
                             'alt-text' => '[ inserted by TIDY ]',
                             'wrap' => 120);
        if (function_exists('tidy_setopt')) { // Tidy 1.0
            foreach ($tidy_config as $field=>$value) {
                tidy_setopt($field, $value);
            }
            tidy_set_encoding('utf8');
            $source = tidy_repair_string($source);

        } else { // Tidy 2.0
            $source = tidy_repair_string($source, $tidy_config, 'utf8');
        }
    }

    // To XHTML
    if ($to_xhtml) {
        // catch inline CSS
        $css = null;
        if (preg_match('/<head.*?>(.*?)<\/head>/is', $source, $matches)) {
            $source = preg_replace('/<head.*?>.*?<\/head>/is', '', $source);
            preg_match_all('/<style(?:.*?type="text\/css".*?)?>(.*?)<\/style>/is', $matches[1], $matches);
            foreach ($matches[1] as &$match) {
                $css .= $match;
            }
            $css = banana_filterCss($css);
            Banana::$page->addCssInline($css);
        }

        // clean DTD
        $source = str_replace('<font', '<span', $source);
        $source = preg_replace('/<u\b/', '<span style="text-decoration: underline"', $source);
        $source = preg_replace('/<\/(font|u)>/', '</span>', $source);
        $source = str_replace('<body', $css ? '<div class="html"' : '<div class="html default"', $source);
        $source = str_replace('</body>', '</div>', $source);
    }
    $allowedTags = '<h1><h2><h3><b><i><a><ul><li><pre><hr><blockquote><img><br><div><span>'
                 . '<p><small><big><sup><sub><code><em><strong><table><tr><td><th>';
    $source = strip_tags($source, $allowedTags);

    // Use inlined style instead of old html attributes
    if ($to_xhtml) {
        $source = preg_replace('/<(\/?\w+)(.*?)(\/?>)/uise', "'<\\1' . banana_cleanStyles('\\1', '\\2') . '\\3'", $source);
    }    
    return preg_replace('/<(.*?)>/ie', "'<'.banana_removeEvilAttributes('\\1').'>'", $source);
}

function banana_catchHtmlSignature($res)
{
    $res = preg_replace("@(</p>)\n?-- ?\n?(<p[^>]*>|<br[^>]*>)@", "\\1<br/>-- \\2", $res);
    $res = preg_replace("@<br[^>]*>\n?-- ?\n?(<p[^>]*>)@", "<br/>-- <br/>\\2", $res);
    $res = preg_replace("@(<pre[^>]*>)\n?-- ?\n@", "<br/>-- <br/>\\1", $res);
    $parts = preg_split("@(:?<p[^>]*>\n?-- ?\n?</p>|<br[^>]*>\n?-- ?\n?<br[^>]*>)@", $res);
    $sign  = '<hr style="width: 100%; margin: 1em 0em; " />';
    return join($sign, $parts);
}

// {{{ Link to part catcher tools

function banana__linkAttachment($cid)
{
    return banana_htmlentities(
        Banana::$page->makeUrl(Array('group' => Banana::$group,
                                     'artid' => Banana::$artid,
                                     'part'  => $cid)));
}

// }}}

function banana_hideExternalImages($text)
{
    if (preg_match("/<img([^>]*?)src=['\"](?!cid).*?['\"](.*?)>/i", $text)) {
        Banana::$msgshow_hasextimages = true;
        return preg_replace("/<img([^>]*?)src=['\"](?!cid).*?['\"](.*?)>/i",
                            '<img\1src="invalid"\2>',
                            $text);
    }
    return $text;
}

function banana_catchPartLinks($text)
{
    $article = Banana::$page->makeURL(array('group' => Banana::$group, 'artid' => Banana::$artid, 'part' => Banana::$part));
    $article = banana_htmlentities($article);
    $text = preg_replace('/cid:([^\'" ]+)/e', "banana__linkAttachment('\\1')", $text);
    $text = preg_replace('/href="(#.*?)"/i', 'href="' . $article . '\1"', $text);
    return $text;
}

// {{{ HTML to Plain Text tools

function banana__convertFormats($res)
{
    $table = array('em|i'     => '/',
                   'strong|b' => '*',
                   'u'        => '_');
    foreach ($table as $tags=>$format) {
        $res = preg_replace("!</?($tags)( .*?)?>!is", $format, $res);
    }
    return $res;
}

function banana__convertQuotes($res)
{
    return preg_replace('!<blockquote.*?>([^<]*)</blockquote>!ies',
                        "\"\n\" . banana_quote(banana__convertQuotes('\\1' . \"\n\"), 1, '&gt;')",
                        $res);
}

// }}}

function banana_htmlToPlainText($res)
{
    $res = str_replace("\n", '', $res);
    $res = banana__convertFormats($res);
    $res = trim(strip_tags($res, '<div><br><p><blockquote>'));
    $res = preg_replace("@</?(br|p|div).*?>@si", "\n", $res);
    $res = banana__convertQuotes($res);
    return banana_html_entity_decode($res);    
}

function banana_formatHtml(BananaMimePart &$part)
{
    $text = $part->getText();
    $text = banana_catchHtmlSignature($text);
    if (!Banana::$msgshow_externalimages) {
        $text = banana_hideExternalImages($text);
    }    
    $text = banana_catchPartLinks($text);
    return banana_cleanHtml($text, true);
}

function banana_quoteHtml(BananaMimePart &$part)
{
    $text = $part->getText();
    $text = banana_htmlToPlainText($text);
    return banana_wrap($text, 1);
}

// }}}
// {{{ Richtext Functions

/** Convert richtext to html
 */
function banana_richtextToHtml($source)
{
    $tags = Array('bold'        => 'b',
                  'italic'      => 'i',
                  'smaller'     => 'small',
                  'bigger'      => 'big',
                  'underline'   => 'u',
                  'subscript'   => 'sub',
                  'superscript' => 'sup',
                  'excerpt'     => 'blockquote',
                  'paragraph'   => 'p',
                  'nl'          => 'br'
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
    return banana_cleanHtml($source);
}

function banana_formatRichText(BananaMimePart &$part)
{
    $text = $part->getText();
    $text = banana_richtextToHtml($text);
    $text = banana_catchHtmlSignature($text);
    return banana_cleanHtml($text);
}

function banana_quoteRichtText(BananaMimePart &$part)
{
    $text = $part->getText();
    $text = banana_richtextToHtml($text);
    $text = banana_htmlToPlainText($text);
    return banana_wrap($text, 1);
}

// }}}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

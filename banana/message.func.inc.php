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

function banana_wordwrap($text, $quote_level)
{
    if ($quote_level > 0) {
        $length = Banana::$msgshow_wrap - $quote_level - 1;
        return banana_quote(wordwrap($text, $length), $quote_level);
    
    }
    return wordwrap($text, Banana::$msgshow_wrap);
}

function banana_catchFormats($text)
{
    $formatting = Array('/' => 'em', // match / first in order not to match closing markups </...> <> </>
                        '_' => 'u',
                        '*' => 'strong');
    $url = Banana::$msgshow_url;
    preg_match_all("/$url/ui", $text, $urls);
    $text = str_replace($urls[0], "&&&urls&&&", $text);
    foreach ($formatting as $limit=>$mark) {
        $limit = preg_quote($limit, '/');
        $text = preg_replace("/$limit\\b(.*?)\\b$limit/s",
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
    return banana_cleanHtml('<pre>' . $text . '</pre>');
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
    
/**
 * @return string
 * @param string
 * @desc Strip forbidden tags and delegate tag-source check to removeEvilAttributes()
 */
function banana_cleanHtml($source)
{
    $allowedTags = '<h1><b><i><a><ul><li><pre><hr><blockquote><img><br><font><div>'
                 . '<p><small><big><sup><sub><code><em><strong><table><tr><td><th>';
    $source = strip_tags($source, $allowedTags);
    $source = preg_replace('/<(.*?)>/ie', "'<'.banana_removeEvilAttributes('\\1').'>'", $source);
        
    if (function_exists('tidy_repair_string')) {
        $tidy_on = Array(
            'drop-empty-paras', 'drop-proprietary-attributes',
            'hide-comments', 'logical-emphasis', 'output-xhtml',
            'replace-color', 'show-body-only'
        );
        $tidy_off = Array('join-classes', 'clean'); // 'clean' may be a good idea, but it is too aggressive

        foreach($tidy_on as $opt) {
            tidy_setopt($opt, true);
        }
        foreach($tidy_off as $opt) {
            tidy_setopt($opt, false);
        }
        tidy_setopt('alt-text', '[ inserted by TIDY ]');
        tidy_setopt('wrap', '120');
        tidy_set_encoding('utf8');
        return tidy_repair_string($source);
    }
    return $source;
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
    return preg_replace("/<img[^>]*?src=['\"](?!cid).*?>/i",
                        Banana::$page->makeImg(array('img' => 'invalid')),
                        $text);
}

function banana_catchPartLinks($text)
{
    return preg_replace('/cid:([^\'" ]+)/e', "banana__linkAttachment('\\1')", $text);
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
    $text = banana_hideExternalImages($text);
    $text = banana_catchPartLinks($text);
    return banana_cleanHtml($text);
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

// }}}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

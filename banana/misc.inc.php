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
    $s = ($c == 'Q') ? quoted_printable_decode($str) : base64_decode($str);
    $s = iconv($charset, 'iso-8859-15', $s);
    return str_replace('_', ' ', $s);
}
 
function headerDecode($value) {
    $val = preg_replace('/(=\?[^?]*\?[BQ]\?[^?]*\?=) (=\?[^?]*\?[BQ]\?[^?]*\?=)/', '\1\2', $value);
    return preg_replace('/=\?([^?]*)\?([BQ])\?([^?]*)\?=/e', '_headerdecode("\1", "\2", "\3")', $val);
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
    switch ($_header) {
        case "date": 
            return formatDate($_text);
        
        case "followup-to":
            case "newsgroups":
            $res = "";
            $groups = preg_split("/[\t ]*,[\t ]*/",$_text);
            foreach ($groups as $g) {
                $res.="<a href='?group=$g'>$g</a>, ";
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
                $rsl .= "<a href=\"?group={$banana->spool->group}&amp;artid=$p\">$ndx</a> ";
                $ndx++;
            }
            return $rsl;

        case "x-face":
            return '<img src="xface.php?face='.base64_encode($_text).'"  alt="x-face" />';
        
        default:
            if (function_exists('hook_formatDisplayHeader')
                    && $res = hook_formatDisplayHeader($_header, $_text))
            {
                return $res;
            }
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

function displayshortcuts($first = -1) {
    global $banana;
    extract($banana->state);

    $res  = '<div class="banana_scuts">';
    $res .= '[<a href="?">'._b_('Liste des forums').'</a>] ';
    if (is_null($group)) {
        return $res.'[<a href="?subscribe=1">'._b_('Abonnements').'</a>]</div>';
    }
   
    $res .= "[<a href=\"?group=$group\">$group</a>] ";

    if (is_null($artid)) {
        $res .= "[<a href=\"?group=$group&amp;action=new\">"._b_('Nouveau message')."</a>] ";
        if (sizeof($banana->spool->overview)>$banana->tmax) {
            $res .= '<br />';
            $n = intval(log(count($banana->spool->overview), 10))+1;
            for ($ndx=1; $ndx <= sizeof($banana->spool->overview); $ndx += $banana->tmax) {
                if ($first==$ndx) {
                    $fmt = "[%0{$n}u-%0{$n}u] ";
                } else {
                    $fmt = "[<a href=\"?group=$group&amp;first=$ndx\">%0{$n}u-%0{$n}u</a>] ";
                }
                $res .= sprintf($fmt, $ndx, min($ndx+$banana->tmax-1,sizeof($banana->spool->overview)));
            }
        }
    } else {
        $res .= "[<a href=\"?group=$group&amp;artid=$artid&amp;action=new\">"
             ._b_('Répondre')."</a>] ";
        if ($banana->post && $banana->post->checkcancel()) {
            $res .= "[<a href=\"?group=$group&amp;artid=$artid&amp;action=cancel\">"
                 ._b_('Annuler ce message')."</a>] ";
        }
    }
    return $res.'</div>';
}

/********************************************************************************
 *  FORMATTING STUFF : BODY
 */

function wrap($text, $_prefix="")
{
    $parts = preg_split("/\n-- ?\n/", $text);
    if (count($parts)  >1) {
        $sign = "\n-- \n" . array_pop($parts);
        $text = join("\n-- \n", $parts);
    } else {
        $sign = '';
    }
   
    global $banana;
    $length = $banana->wrap;
    $cmd = "echo ".escapeshellarg($text)." | perl -MText::Autoformat -e 'autoformat {left=>1, right=>$length, all=>1 };'";
    $ret = 0;
    exec($cmd, $result, $ret);
    if ($ret != 0) {
        $result = split("\n", $text);
    }

    return $_prefix.join("\n$_prefix", $result).($_prefix ? '' : $sign);
}

function formatbody($_text, $format='plain')
{
    if ($format == 'html') {
        $res = '<br/>'.html_entity_decode(to_entities(removeEvilTags($_text))).'<br/>';
    } else if ($format == 'richtext') {
        $res = '<br/>'.html_entity_decode(to_entities(richtextToHtml($_text))).'<br/>';
        $format = 'html';
    } else {
        $res  = "\n\n" . to_entities(wrap($_text, ""))."\n\n";
    }
    $res  = preg_replace("/(&lt;|&gt;|&quot;)/", " \\1 ", $res);
    $res  = preg_replace('/(["\[])?((https?|ftp|news):\/\/[a-z@0-9.~%$£µ&i#\-+=_\/\?]*)(["\]])?/i', '\1<a href="\2">\2</a>\4', $res);
    $res  = preg_replace("/ (&lt;|&gt;|&quot;) /", "\\1", $res);

    if ($format == 'html') {
        $res = preg_replace("@(</p>)\n?-- \n?(<p[^>]*>|<br[^>]*>)@", "\\1<br/>-- \\2", $res);
        $res = preg_replace("@<br[^>]*>\n?-- \n?(<p[^>]*>)@", "<br/>-- <br/>\\2", $res);
        $parts = preg_split("@(:?<p[^>]*>\n?-- \n?</p>|<br[^>]*>\n?-- \n?<br[^>]*>)@", $res);
    } else {
        for ($i = 1 ; preg_match("@(^|<pre>|\n)&gt;@i", $res) ; $i++) {
            $res  = preg_replace("@(^|<pre>|\n)((&gt;[^\n]*\n)+)@ie",
                "'\\1</pre><blockquote class=\'level$i\'><pre>'"
    		    .".stripslashes(preg_replace('@(^|<pre>|\n)&gt;[ \\t\\r]*@i', '\\1', '\\2'))"
	    	    .".'</pre></blockquote><pre>'",
	            $res);
        }
	$res = preg_replace("@<pre>-- ?\n@", "<pre>\n-- \n", $res);
        $parts = preg_split("/\n-- ?\n/", $res);
    }

    if (count($parts) > 1) {
        $sign  = array_pop($parts);
        if ($format == 'html') {
            $res  = join('<br/>-- <br/>', $parts);
            $sign = '<hr style="width: 100%; margin: 1em 0em; " />'.$sign.'<br/>';
        } else {
            $res  = join('\n-- \n', $parts);
            $sign = '</pre><hr style="width: 100%; margin: 1em 0em; " /><pre>'.$sign;
        }
        return $res.$sign;
    } else {
        return $res;
    }
}

?>

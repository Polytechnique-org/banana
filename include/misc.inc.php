<?php
/********************************************************************************
* include/misc.inc.php : Misc functions
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

function _headerdecode($charset, $c, $str) {
    $s = ($c == 'Q') ? quoted_printable_decode($str) : base64_decode($str);
    $s = iconv($charset, 'iso-8859-15', $s);
    return str_replace('_', ' ', $s);
}
 
function headerDecode($value) {
    $val = preg_replace('/(=\?[^?]*\?[BQ]\?[^?]*\?=) (=\?[^?]*\?[BQ]\?[^?]*\?=)/', '\1\2', $value);
    return preg_replace('/=\?([^?]*)\?([BQ])\?([^?]*)\?=/e', '_headerdecode("\1", "\2", "\3")', $val);
}

function header_translate($hdr) {
    switch (strtolower($hdr)) {
        case 'from':            return _('De');
        case 'subject':         return _('Sujet');
        case 'newsgroups':      return _('Forums');
        case 'followup':        return _('Suivi-à');
        case 'date':            return _('Date');
        case 'organization':    return _('Organisation');
        case 'references':      return _('Références');
        case 'xface':           return _('Image');
        default:
            return $hdr;
    }
}

function formatDate($_text) {
    return strftime("%A %d %B %Y, %H:%M (fuseau serveur)", strtotime($_text));
}

function fancyDate($stamp) {
    $today  = intval(time() / (24*3600));
    $dday   = intval($stamp / (24*3600));

    if ($today == $dday) {
        $format = "%H:%M";
    } elseif ($today == 1 + $dday) {
        $format = _('hier')." %H:%M";
    } elseif ($today < 7 + $dday) {
        $format = '%A %H:%M';
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

function wrap($text, $_prefix="", $_length=72)
{
    $parts = preg_split("/\n-- ?\n/", $text);
    if (count($parts)  >1) {
        $sign = "\n-- \n" . array_pop($parts);
        $text = join("\n-- \n", $parts);
    } else {
        $sign = '';
        $text = $text;
    }
    
    $cmd = "echo ".escapeshellarg($text)." | perl -MText::Autoformat -e 'autoformat {left=>1, right=>$_length, all=>1 };'";
    exec($cmd, $result);

    return $_prefix.join("\n$_prefix", $result).($_prefix ? '' : $sign);
}

function formatbody($_text) {
    global $news;
    $res  = "\n\n" . htmlentities(wrap($_text, "", $news['wrap']))."\n\n";
    $res  = preg_replace("/(&lt;|&gt;|&quot;)/", " \\1 ", $res);
    $res  = preg_replace('/(["\[])?((https?|ftp|news):\/\/[a-z@0-9.~%$£µ&i#\-+=_\/\?]*)(["\]])?/i', "\\1<a href=\"\\2\">\\2</a>\\4", $res);
    $res  = preg_replace("/ (&lt;|&gt;|&quot;) /", "\\1", $res);
   
    $parts = preg_split("/\n-- ?\n/", $res);

    if (count($parts) > 1) {
        $sign = "</pre><hr style='width: 100%; margin: 1em 0em; ' /><pre>" . array_pop($parts);
        return join("\n-- \n", $parts).$sign;
    } else {
        return $res;
    }
}

?>

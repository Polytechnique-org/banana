<?php
/********************************************************************************
* include/wrapper.inc.php : text wrapper 
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

function wrap($_text, $_prefix="", $_length=72)
{
    $parts = preg_split("/\n-- ?\n/", $_text);
    if (count($parts)  >1) {
        $sign = "\n-- \n" . array_pop($parts);
        $text = join("\n-- \n", $parts);
    } else {
        $sign = '';
        $text = $_text;
    }
    
    $cmd = "echo ".escapeshellarg($text)." | perl -MText::Autoformat -e 'autoformat {left=>1, right=>$_length, all=>1 };'";
    exec($cmd, $result);

    return $_prefix.join("\n$_prefix", $result).($_prefix ? '' : $sign);
}

function _headerdecode($charset, $c, $str) {
    $s = ($c == 'Q') ? quoted_printable_decode($str) : base64_decode($str);
    $s = iconv($charset, 'iso-8859-15', $s);
    return str_replace('_', ' ', $s);
}
 
function headerDecode($value) {
    $val = preg_replace('/(=\?[^?]*\?[BQ]\?[^?]*\?=) (=\?[^?]*\?[BQ]\?[^?]*\?=)/', '\1\2', $value);
    return preg_replace('/=\?([^?]*)\?([BQ])\?([^?]*)\?=/e', '_headerdecode("\1", "\2", "\3")', $val);
}

?>

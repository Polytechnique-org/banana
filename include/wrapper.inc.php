<?php
/********************************************************************************
* include/wrapper.inc.php : text wrapper 
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

function wrap($_text,$_prefix="",$_length=72) {
    $parts = preg_split("/\n-- ?\n/", $_text);
    if (count($parts)  >1) {
        $sign = "\n-- \n" . array_pop($parts);
        $text = join("\n-- \n", $parts);
    } else {
        $text = $_text;
        $sign = '';
    }
    if ($_prefix != "") {
        $lines = split("\n",$_text);
        $text = $_prefix.join("\n$_prefix",$lines);
    }
    $exec="echo ".escapeshellarg($text)." | perl -MText::Autoformat -e 'autoformat {left=>1, right=>$_length, all=>1 };'";
    exec($exec,$result);

    return join("\n",$result).$sign;
}

?>

<?php
/********************************************************************************
* include/encoding.inc.php : decoding subroutines
* --------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** Decodes quoted-printable and UTF8 headers
 * @param STRING $_value string to decode
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
?>

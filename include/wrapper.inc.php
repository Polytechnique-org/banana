<?php
/********************************************************************************
* include/wrapper.inc.php : text wrapper 
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** recursive function deligne : extracts structure of a text
 * @param $_text STRING text
 * @return ARRAY recursive structure
 * @see dowrap
 * @see part_wrap
 * @see wrap
 */

function deligne($_text) {
// lines => array of source text
  $lines = split("\n",$_text);
// array => final result
  $array = array();

// build a part
  $part = "";
// we process non quoted text at the beginning of the post
  $quoted = false;
  for ($i=0; $i<count($lines);$i++) {
    $l = $lines[$i];
    if ($quoted) {
      if ((!preg_match("/^(>|\||:) ?/",$l)) || ($i+1==count($lines))) {
// line $i normal text, and former line is quoted
        if ($i+1==count($lines))
// last line : updates $part
          $part .= "$l\n";
// updates array and switch to non-quoted text
        $array[] = deligne($part); 
        $quoted = false;
        $part = $l."\n";
      } else {
// line $i and former line are quoted : updates $part
        $part .= preg_replace("/^(>|\||:) ?/","",$l)."\n";
      }
    } else {
      if ((preg_match("/^(>|\||:) ?/",$l)) || ($i+1==count($lines))) {
// line $i quoted text, and former line is normal
        if ($i+1==count($lines))
// last line : updates $part 
          $part .= "$l\n";
// updates array and switch to quoted text
        $array[] = substr($part,0,-1);
        $quoted = true;
        $part = preg_replace("/^(>|\||:) ?/","",$l)."\n";
      } else {
// line $i and former line are normal : updates $part
        $part .= $l."\n";
      }
    }
  }
  return $array;
}

/** wrapping function : "intelligent wrap"
 * @param $_text STRING text to wrap
 * @param $_maxls INTEGER source text wrap length
 * @param $_maxld INTEGER destination text wrap length
 * @return STRING wrapped text
 * @see dowrap
 * @see deligne
 * @see wrap
 */

function part_wrap($_text,$_maxls,$_maxld=72) {
// lines => array of source text lines
  $lines = split("\n",$_text);
// last => size of last line processed
  $last = -1; 
// result => final result
  $result = "";
  foreach ($lines as $l) {
    if ($last != -1) {
// skip first line
      if (strpos($l," ")+$last<$_maxls) { 
// if first word is too short, this line begins a new paragraph 
        $result .= "\n";
      } else {
// if not, continue on the same line
        $result .= " ";
      }
    }
    $result .= chop($l);
    $last = strlen($l);
  }
  return wordwrap($result,$_maxld,"\n",0);
}

/** recursive function : rewrap quoted text
 * @param $_mixed ARRAY structure  
 * @param $_maxls INTEGER source wrap length
 * @param $_maxld INTEGER destination wrap length
 * @param $_prefix STRING prefix for destination text
 * @return STRING wrapped text
 * @see deligne
 * @see part_wrap
 * @see wrap
 */

function dowrap($_mixed,$_maxls=72,$_maxld=72,$_prefix="") {
// result => final result
  $result = "";
// array : it is quoted text. loop on content to process
  if (is_array($_mixed)) {
    foreach ($_mixed as $d) {
      $result.=dowrap($d,$_maxls,$_maxld,$_prefix.">");
    }
// text : "normal" text. return wrapped text
  } else {
// computes destination text size [Assume no software wraps text under 65 chars]
    $maxls = max(max(array_map("strlen",split("\n",$_mixed))),65);
// wraps text
    $preresult = part_wrap($_mixed,$_maxls,$_maxld-strlen($_prefix." "));
// adds prefix 
    $lines = split("\n",$preresult);
    foreach ($lines as $l) {
      $result .= $_prefix.(strlen($_prefix)>0?" ":"")."$l\n";
    }
  //  $result = substr($result,0,-1);
  }
  return $result;
}


/** wrap function 
 * @param $_text STRING text 
 * @param $_prefix STRING prefix (eg. quotes)
 * @param $_length STRING wrap length
 * @return STRING wrapped text
 * @see deligne
 * @see part_wrap
 * @see dowrap
 */

function wrap($_text,$_prefix="",$_length=72) {
#  $lines = split("\n",$_text);
#  $max = max(array_map("strlen",$lines));
#  $delig = deligne($_text);
#  $result = "";

#  foreach ($delig as $d) {
#    $result .= dowrap($d,$max,$_length,$_prefix);
#  }
  
  $text = $_text;
  if ($_prefix != "") {
	$lines = split("\n",$_text);
	$text = $_prefix.join($lines,"\n$_prefix");
  }
  exec("echo ".escapeshellarg($text)." | perl -MText::Autoformat -e "
   ."'{autoformat{ignore=>qr/^([a-zA-Z]+:|--)/};}'", $result);
  $result=preg_replace("/\n--\n/","\n-- \n",join($result,"\n"));

  return $result;
}

?>



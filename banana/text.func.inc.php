<?php
/********************************************************************************
* banana/text.php : text tools
* ---------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

if (function_exists('dgettext')) {
    function _b_($str)
    {
        return dgettext('banana', $str);
    }
} else {
    function _b_($str)
    {
        return $str;
    }
}

if (!function_exists('is_utf8')) {
    function is_utf8($s)
    {
        return @iconv('utf-8', 'utf-8', $s) == $s;
    }
}

function banana_utf8entities($source)
{
   // array used to figure what number to decrement from character order value 
   // according to number of characters used to map unicode to ascii by utf-8
   $decrement[4] = 240;
   $decrement[3] = 224;
   $decrement[2] = 192;
   $decrement[1] = 0;
   
   // the number of bits to shift each charNum by
   $shift[1][0] = 0;
   $shift[2][0] = 6;
   $shift[2][1] = 0;
   $shift[3][0] = 12;
   $shift[3][1] = 6;
   $shift[3][2] = 0;
   $shift[4][0] = 18;
   $shift[4][1] = 12;
   $shift[4][2] = 6;
   $shift[4][3] = 0;
   
   $pos = 0;
   $len = strlen($source);
   $encodedString = '';
   while ($pos < $len)
   {
      $charPos = $source{$pos};
      $asciiPos = ord($charPos);
      if ($asciiPos < 128)
      {
         $encodedString .= $charPos;
         $pos++;
         continue;
      }
      
      $i=1;
      if (($asciiPos >= 240) && ($asciiPos <= 255)) // 4 chars representing one unicode character
         $i=4;
      else if (($asciiPos >= 224) && ($asciiPos <= 239)) // 3 chars representing one unicode character
         $i=3;
      else if (($asciiPos >= 192) && ($asciiPos <= 223)) // 2 chars representing one unicode character
         $i=2;
      else // 1 char (lower ascii)
         $i=1;
      $thisLetter = substr($source, $pos, $i);
      $pos += $i;
      
      // process the string representing the letter to a unicode entity
      $thisLen = strlen($thisLetter);
      $thisPos = 0;
      $decimalCode = 0;
      while ($thisPos < $thisLen)
      {
         $thisCharOrd = ord(substr($thisLetter, $thisPos, 1));
         if ($thisPos == 0)
         {
            $charNum = intval($thisCharOrd - $decrement[$thisLen]);
            $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
         }
         else
         {
            $charNum = intval($thisCharOrd - 128);
            $decimalCode += ($charNum << $shift[$thisLen][$thisPos]);
         }
         
         $thisPos++;
      }
      
      $encodedLetter = '&#'. str_pad($decimalCode, ($thisLen==1)?3:5, '0', STR_PAD_LEFT).';';
      $encodedString .= $encodedLetter;
   }
   
   return $encodedString;
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

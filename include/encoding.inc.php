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

function headerDecode($_value) {
  if (eregi('=\?.*\?.\?.*\?=',$_value)) { // is there anything encoded ?
    if (eregi('=\?(.*)\?Q\?.*\?=',$_value,$charset)) {  // quoted-printable decoding
      $result1=eregi_replace('(.*)=\?.*\?Q\?(.*)\?=(.*)','\1',$_value);
      $result2=eregi_replace('(.*)=\?.*\?Q\?(.*)\?=(.*)','\2',$_value);
      $result3=eregi_replace('(.*)=\?.*\?Q\?(.*)\?=(.*)','\3',$_value);
      $result2=str_replace("_"," ",quoted_printable_decode($result2));
      if ($charset[1] == "UTF-8") 
        $result2 = utf8_decode($result2);
      $newvalue=$result1.$result2.$result3;
    }
    if (eregi('=\?(.*)\?B\?.*\?=',$_value,$charset)) {  // base64 decoding
      $result1=eregi_replace('(.*)=\?.*\?B\?(.*)\?=(.*)','\1',$_value);
      $result2=eregi_replace('(.*)=\?.*\?B\?(.*)\?=(.*)','\2',$_value);
      $result3=eregi_replace('(.*)=\?.*\?B\?(.*)\?=(.*)','\3',$_value);
      $result2=base64_decode($result2);
      if ($charset[1] == "UTF-8") 
        $result2 = utf8_decode($result2);
      $newvalue=$result1.$result2.$result3;
    }
    if (!isset($newvalue)) // nothing of the above, must be an unknown encoding...
      $newvalue=$_value;
    else
      $newvalue=headerDecode($newvalue);  // maybe there are more encoded
    return($newvalue);                    // parts
  } else {   // there wasn't anything encoded, return the original string
    return($_value);
  }
}
?>

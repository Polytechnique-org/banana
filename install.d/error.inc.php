<?php
/********************************************************************************
* install.d/error.inc.php : central NNTP error messages
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

 /** outputs HTML error page
  * @param $_type STRING error type
  */

function error($_type) {
  global $locale,$css,$group;
  switch ($_type) {
    case "nntpsock":
      echo "<p class=\"error\">\n\t".$locale['error']['connect']."\n</p>";
      require("include/footer.inc.php");
      exit;
      break;  
    case "nntpauth":
      echo "<p class=\"error\">\n\t".$locale['error']['credentials']
        ."\n</p>";
      require("include/footer.inc.php");
      exit;
      break;
    case "nntpgroups":
      echo "<p class=\"{$css['normal']}\">";
      echo "\n".$locale['error']['nogroup']."\n";
      echo "</p>\n";
      require("include/footer.inc.php");
      exit;
      break;
    case "nntpspool":
      echo "<div class=\"{$css['bananashortcuts']}\">\n";
      echo "[<a href=\"index.php\">Liste des forums</a>]\n";
      echo "</div>\n";
      echo "<p class=\"error\">\n\t".$locale['error']['group']."\n</p>";
      require("footer.inc.php");
      exit;
      break;
    case "nntpart":
      echo "<div class=\"{$css['bananashortcuts']}\">\n";
      echo "[<a href=\"index.php\">Liste des forums</a>] \n";
      echo "[<a href=\"thread.php?group=$group\">$group</a>] \n";
      echo "</div>\n";
      echo "<p class=\"error\">\n\t".$locale['error']['post']."\n</p>";
      require("footer.inc.php");
      exit;
      break;
  }
}

?>

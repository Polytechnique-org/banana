<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require("locales/locales.inc.php");
require("include/session.inc.php");
require("include/password.inc.php");
require("include/NetNNTP.inc.php");
require("include/groups.inc.php");
require("include/format.inc.php");
require("include/config.inc.php");

require("include/header.inc.php");

$nntp = new nntp($news['server']);
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) {
    echo "<p class=\"error\">\n\t".$locale['error']['credentials']
      ."\n</p>";
    require("include/footer.inc.php");
    exit;
  }
}
$groups = new groups($nntp);

?>

<div class="title">
  <?php echo $locale['index']['title'];?>
</div>

<?php
if (!sizeof($groups->overview)) {
  echo '<p class="normal">';
  echo "\n".$locale['error']['nogroup']."\n";
  echo "</p>\n";
  require("include/footer.inc.php");
  exit;
}

displayshortcuts();
?>

<table class="bicol" cellspacing="0" cellpadding="2" 
  summary="<?php echo $locale['index']['summary'];?>">
  <tr>
    <th>
      <?php echo $locale['index']['total'];?>
    </th>
    <th>
      <?php echo $locale['index']['unread'];?>
    </th>
    <th>
      <?php echo $locale['index']['name'];?>
    </th>
    <th>
      <?php echo $locale['index']['description'];?>
    </th>
  </tr>
<?php
$pair = true;
foreach ($groups->overview as $g => $d) {
  $pair = !$pair;
  $groupinfo = $nntp->group($g);
?>
  <tr class="<?php echo ($pair?"pair":"impair");?>" >
    <td class="total">
      <?php echo $groupinfo[0]; ?>
    </td>
    <td class="unread">
      0
    </td>
    <td class="group">
      <?php echo "<a href=\"thread.php?group=$g\">$g</a>";?>
    </td>
    <td class="description">
      <?php echo $d[0];?>
    </td>
  </tr>
<?php
}
?>
</table>
<?php

displayshortcuts();

$nntp->quit();
require("include/footer.inc.php");
?>

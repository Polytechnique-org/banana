<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require("include/session.inc.php");
require("include/password.inc.php");
require("include/NetNNTP.inc.php");
require("include/groups.inc.php");
require("include/format.inc.php");
require("include/config.inc.php");
require("include/profile.inc.php");
require("include/error.inc.php");

$profile=getprofile();
require($profile['locale']);

require("include/header.inc.php");

$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) error("nntpauth");
}
$groups = new groups($nntp,0);
if (!count($groups->overview)) $groups=new groups($nntp,2)
    
$newgroups = new groups($nntp,1);
?>

<div class="<?php echo $css["title"];?>">
  <?php echo $locale['index']['title'];?>
</div>

<?php
if (!sizeof($groups->overview)) error("nntpgroups");

displayshortcuts();
?>

<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2" 
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
  $newarts = $nntp->newnews($profile['lastnews'],$g);
?>
  <tr class="<?php echo ($pair?$css["pair"]:$css["impair"]);?>" >
    <td class="<?php echo $css["total"]; ?>">
      <?php echo $groupinfo[0]; ?>
    </td>
    <td class="<?php echo $css["unread"]; ?>">
      <?php echo sizeof($newarts); ?>
    </td>
    <td class="<?php echo $css["group"]; ?>">
      <?php echo "<a href=\"thread.php?group=$g\">$g</a>";?>
    </td>
    <td class="<?php echo $css["description"]; ?>">
      <?php echo $d[0];?>
    </td>
  </tr>
<?php
}
?>
</table>
<?php
if (count($newgroups->overview) and count($profile['subscribe'])) {
?>
<p class="normal">
<?php echo $locale['index']['newgroupstext']; ?>
</p>
<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2" 
  summary="<?php echo $locale['index']['summary'];?>">
  <tr>
    <th>
      <?php echo $locale['index']['total'];?>
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
  foreach ($newgroups->overview as $g => $d) {
    $pair = !$pair;
    $groupinfo = $nntp->group($g);
?>
  <tr class="<?php echo ($pair?$css["pair"]:$css["impair"]);?>" >
    <td class="<?php echo $css["total"]; ?>">
      <?php echo $groupinfo[0]; ?>
    </td>
    <td class="<?php echo $css["group"]; ?>">
      <?php echo "<a href=\"thread.php?group=$g\">$g</a>";?>
    </td>
    <td class="<?php echo $css["description"]; ?>">
      <?php echo $d[0];?>
    </td>
  </tr>
<?php
  } //foreach
?>
</table>
<?php
} // new newsgroups ?

displayshortcuts();

$nntp->quit();
require("include/footer.inc.php");
?>

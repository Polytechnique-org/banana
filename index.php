<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/session.inc.php");
require_once("include/misc.inc.php");
require_once("include/password.inc.php");
require_once("include/NetNNTP.inc.php");
require_once("include/groups.inc.php");
require_once("include/format.inc.php");
require_once("include/config.inc.php");
require_once("include/profile.inc.php");
require_once("include/error.inc.php");

$profile=getprofile();
require_once("include/header.inc.php");

$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) error("nntpauth");
}
$groups = new BananaGroups($nntp,0);
if (!count($groups->overview)) $groups = new BananaGroups($nntp,2);
    
$newgroups = new BananaGroups($nntp,1);
?>

<h1>
  <?php echo _b_('Les forums de Banana'); ?>
</h1>

<?php
if (!sizeof($groups->overview)) error("nntpgroups");

displayshortcuts();
?>

<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2">
  <tr>
    <th>
      <?php echo _b_('Total'); ?>
    </th>
    <th>
      <?php echo _b_('Nouveaux'); ?>
    </th>
    <th>
      <?php echo _b_('Nom'); ?>
    </th>
    <th>
      <?php echo _b_('Description'); ?>
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
<?php echo _b_('Les forums suivants ont été créés depuis ton dernier passage :'); ?>
</p>
<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2">
  <tr>
    <th>
      <?php echo _b_('Total'); ?>
    </th>
    <th>
      <?php echo _b_('Nom'); ?>
    </th>
    <th>
      <?php echo _b_('Description'); ?>
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
require_once("include/footer.inc.php");
?>

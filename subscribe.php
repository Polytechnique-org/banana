<?php
/********************************************************************************
* subscribe.php : subscriptions page
* ---------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/session.inc.php");
require_once("include/password.inc.php");
require_once("include/NetNNTP.inc.php");
require_once("include/groups.inc.php");
require_once("include/format.inc.php");
require_once("include/config.inc.php");
require_once("include/profile.inc.php");
require_once("include/subscribe.inc.php");
require_once("include/error.inc.php");

$profile=getprofile();
require_once("include/header.inc.php");

$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) error("nntpauth");
}
$groups = new groups($nntp,2);
?>

<h1>
  <?php echo _('Abonnements'); ?>
</h1>

<?php

if (isset($_POST['subscribe']) && isset($_POST['action']) 
  && $_POST['action']=="OK") {
  update_subscriptions($_POST['subscribe']);
  $profile['subscribe']=$_POST['subscribe'];
}

if (!sizeof($groups->overview)) error("nntpgroups");

displayshortcuts();
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2">
  <tr>
    <th>
      <?php echo _('Total'); ?>
    </th>
    <th>
      <?php echo _('Abonné'); ?>
    </th>
    <th>
      <?php echo _('Nom'); ?>
    </th>
    <th>
      <?php echo _('Description'); ?>
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
      <input type="checkbox" name="subscribe[]" value="<?php echo $g;?>"
      <?php echo (in_array($g,$profile['subscribe'])?'checked="checked"'
      :'');?> />
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
  <tr class="<?php echo (!$pair?$css["pair"]:$css["impair"]); ?>">
    <td colspan="4" class="bouton">
      <input type="submit" name="action" value="OK" />
    </td>
  </tr>
</table>
</form>
<?php

displayshortcuts();

$nntp->quit();
require_once("include/footer.inc.php");
?>

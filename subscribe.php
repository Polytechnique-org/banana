<?php
/********************************************************************************
* subscribe.php : subscriptions page
* ---------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

$groups = new BananaGroups(2);
?>

<h1>
  <?php echo _b_('Abonnements'); ?>
</h1>

<?php

if (isset($_POST['subscribe']) && isset($_POST['action']) && $_POST['action']=="OK") {
    update_subscriptions($_POST['subscribe']);
    $banana->profile['subscribe']=$_POST['subscribe'];
}

if (!sizeof($groups->overview)) error("nntpgroups");

displayshortcuts();
?>

<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<table class="bicol" cellspacing="0" cellpadding="2">
  <tr>
    <th>
      <?php echo _b_('Total'); ?>
    </th>
    <th>
      <?php echo _b_('Abonné'); ?>
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
  $groupinfo = $banana->nntp->group($g);
  $newarts = $banana->nntp->newnews($banana->profile['lastnews'], $g);
?>
  <tr class="<?php echo ($pair?"pair":"impair");?>" >
    <td class="tot">
      <?php echo $groupinfo[0]; ?>
    </td>
    <td class="new">
      <input type="checkbox" name="subscribe[]" value="<?php echo $g;?>"
      <?php if (in_array($g, $banana->profile['subscribe'])) echo 'checked="checked"'; ?> />
    </td>
    <td class="grp">
      <?php echo "<a href=\"thread.php?group=$g\">$g</a>";?>
    </td>
    <td class="dsc">
      <?php echo $d[0];?>
    </td>
  </tr>
<?php
}
?>
  <tr class="<?php echo (!$pair?"pair":"impair"); ?>">
    <td colspan="4" class="bouton">
      <input type="submit" name="action" value="OK" />
    </td>
  </tr>
</table>
</form>
<?php

displayshortcuts();

$banana->nntp->quit();
require_once("include/footer.inc.php");
?>

<?php
/********************************************************************************
* subscribe.php : subscriptions page
* ---------------
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
require("include/subscribe.inc.php");
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
$groups = new groups($nntp,2);
?>

<div class="<?php echo $css["title"];?>">
  <?php echo $locale['subscribe']['title'];?>
</div>

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
<table class="<?php echo $css["bicol"];?>" cellspacing="0" cellpadding="2" 
  summary="<?php echo $locale['subscribe']['summary'];?>">
  <tr>
    <th>
      <?php echo $locale['subscribe']['total'];?>
    </th>
    <th>
      <?php echo $locale['subscribe']['subscribed'];?>
    </th>
    <th>
      <?php echo $locale['subscribe']['name'];?>
    </th>
    <th>
      <?php echo $locale['subscribe']['description'];?>
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
require("include/footer.inc.php");
?>

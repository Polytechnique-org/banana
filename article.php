<?php
/********************************************************************************
* article.php : article page
* -------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require("include/session.inc.php");
require("include/encoding.inc.php");
require("include/wrapper.inc.php");
require("include/format.inc.php");
require("include/config.inc.php");
require("include/NetNNTP.inc.php");
require("include/spool.inc.php");
require("include/post.inc.php");
require("include/profile.inc.php");
require("include/password.inc.php");

$profile=getprofile();
require($profile['locale']);

require("include/header.inc.php");

$group=strtolower(htmlentities($_REQUEST['group']));

$mynntp = new nntp($news['server']);
if (!$mynntp) {
  echo "<p class=\"error\">\n\t".$locale['error']['connect']."\n</p>";
  require("include/footer.inc.php");
  exit;
}

if ($news['user']!="anonymous") {
  $result = $mynntp->authinfo($news["user"],$news["pass"]);
  if (!$result) {
    echo "<p class=\"error\">\n\tYou have provided bad credentials to "
    ."the server. Good bye !\n</p>";
    require("include/footer.inc.php");
    exit;
  }
}
$spool = new spool($mynntp,$group,$profile['display'],$profile['lastnews']);
if (!$spool) {
  echo "<p class=\"error\">\n\tError while accessing group.\n</p>";
  require("include/footer.inc.php");
  exit;
}
$mynntp->group($group);

$post = new post($mynntp,$_REQUEST['id']);
if (!$post) {
  if ($mynntp->lasterrorcode == "423") {
    $spool->delid($_REQUEST['id']);
  }
  echo "<p class=\"error\">\n\tError while reading message.\n</p>";
  require("include/footer.inc.php");
  exit;
}

$ndx = $spool->getndx($_REQUEST['id']);

?>
<div class="title">
  <?php echo $locale['article']['message'];?>
</div>

<?php
if (isset($_GET['type']) && ($_GET['type']=='cancel') && (checkcancel($post->headers))) {
?>
<p class="error">
  <?php echo $locale['article']['cancel'];?>
  <form action="thread.php" method="post">
    <input type="hidden" name="group" value="<?php echo $group;?>" />
    <input type="hidden" name="id" value="<?php 
      echo $_REQUEST['id'];?>" />
    <input type="hidden" name="type" value="cancel" />
    <input type="submit" name="action" value="<?php echo 
      $locale['article']['okbtn'];?>" />
  </form>
</p>
<?
}

displayshortcuts();
?>

<table class="bicol" cellpadding="0" cellspacing="0" 
summary="<?php echo $locale['article']['summary'];?>">
  <tr>
    <th colspan="2">
      <?php echo $locale['article']['headers'];?>
    </th>
  </tr>
<?php
foreach ($news['head'] as $real => $nick) {
  if (isset($post->headers->$nick)) 
    echo "<tr><td class=\"bicoltitre\">$real</td>"
    ."<td>".formatdisplayheader($nick,$post->headers->$nick,$spool)
    ."</td></tr>\n";
}
?>
  <tr>
    <th colspan="2">
      <?php echo $locale['article']['body'];?>
    </th>
  </tr> 
  <tr>
    <td colspan="2">
      <pre><?php echo formatbody($post->body); ?></pre>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo $locale['article']['overview'];?>
    </th>
  </tr> 
  <tr>
    <td class="nopadd" colspan="2">
      <table class="overview" cellpadding="0" cellspacing="0" summary="overview">
<?php
$spool->disp($ndx-$news['threadtop'],$ndx+$news['threadbottom'],$ndx);
?>
      </table>
    </td>
  </tr>
</table>
<?php
displayshortcuts();

require("include/footer.inc.php");
?>

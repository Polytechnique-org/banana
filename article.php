<?php
/********************************************************************************
* article.php : article page
* -------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/session.inc.php");
require_once("include/misc.inc.php");
require_once("include/format.inc.php");
require_once("include/config.inc.php");
require_once("include/NetNNTP.inc.php");
require_once("include/spool.inc.php");
require_once("include/post.inc.php");
require_once("include/profile.inc.php");
require_once("include/password.inc.php");
require_once("include/error.inc.php");

$profile = getprofile();
require_once("include/header.inc.php");

if (isset($_REQUEST['group'])) {
  $group=htmlentities(strtolower($_REQUEST['group']));
}
if (isset($_REQUEST['id'])) {
  $id=htmlentities(strtolower($_REQUEST['id']));
}

$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) error("nntpauth");
}
$spool = new spool($nntp,$group,$profile['display'],$profile['lastnews']);
if (!$spool) error("nntpspool");
$nntp->group($group);

$post = new NNTPPost($nntp,$id);
if (!$post) {
  if ($nntp->lasterrorcode == "423") {
    $spool->delid($id);
  }
  error("nntpart");
}

$ndx = $spool->getndx($id);

?>
<h1>
  <?php echo _('Message'); ?>
</h1>

<?php
if (isset($_GET['type']) && ($_GET['type']=='cancel') && (checkcancel($post->headers))) {
?>
<p class="<?php echo $css['error']?>">
  <?php echo _('Voulez-vous vraiment annuler ce message ?'); ?>
</p>
<form action="thread.php" method="post">
  <input type="hidden" name="group" value="<?php echo $group;?>" />
  <input type="hidden" name="id" value="<?php 
    echo $id;?>" />
  <input type="hidden" name="type" value="cancel" />
  <input type="submit" name="action" value="<?php echo _('OK'); ?>" />
</form>
<?
}

displayshortcuts();
?>

<table class="<?php echo $css['bicol']?>" cellpadding="0" cellspacing="0" 
summary="<?php echo _('Contenu du message'); ?>">
  <tr>
    <th colspan="2">
      <?php echo _('En-têtes'); ?>
    </th>
  </tr>
<?php
foreach ($news['headdisp'] as $nick) {
  if (isset($post->headers->$nick)) 
    echo "<tr><td class=\"{$css['bicoltitre']}\">".header_translate($nick)."</td>"
    ."<td>".formatdisplayheader($nick,$post->headers->$nick,$spool)
    ."</td></tr>\n";
}
?>
  <tr>
    <th colspan="2">
      <?php echo _('Corps'); ?>
    </th>
  </tr> 
  <tr>
    <td colspan="2">
      <pre><?php echo formatbody($post->body); ?></pre>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo _('Aperçu'); ?>
    </th>
  </tr> 
  <tr>
    <td class="<?php echo $css['nopadd']?>" colspan="2">
      <table class="<?php echo $css['overview']?>" cellpadding="0" 
      cellspacing="0" summary="overview">
<?php
$spool->disp($ndx-$news['threadtop'],$ndx+$news['threadbottom'],$ndx);
?>
      </table>
    </td>
  </tr>
</table>
<?php
displayshortcuts();

require_once("include/footer.inc.php");
?>

<?php
/********************************************************************************
* article.php : article page
* -------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

if (isset($_REQUEST['group'])) {
    $group = htmlentities(strtolower($_REQUEST['group']));
}
if (isset($_REQUEST['id'])) {
    $id = htmlentities(strtolower($_REQUEST['id']));
}

echo $banana->action_showArticle($group, $id);

if (isset($_GET['type']) && $_GET['type']=='cancel' && $banana->post->checkcancel()) {
?>
<p class="error">
  <?php echo _b_('Voulez-vous vraiment annuler ce message ?'); ?>
</p>
<form action="thread.php" method="post">
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="id" value="<?php  echo $id; ?>" />
  <input type="hidden" name="type" value="cancel" />
  <input type="submit" name="action" value="<?php echo _b_('OK'); ?>" />
</form>
<?
}

require_once("include/footer.inc.php");
?>

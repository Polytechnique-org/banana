<?php
/********************************************************************************
* disconnect.php : exit page
* ----------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");

$_SESSION=array();
session_destroy();

require_once("include/header.inc.php");
?>
<div class="title">
  <?php echo _b_('Déconnexion effectuée !'); ?>
</div>
<p class="normal">
  <?php echo _b_('Retour au <a href="index.php">profil</a>'); ?>
</p>
<?php
require_once("include/footer.inc.php");
?>

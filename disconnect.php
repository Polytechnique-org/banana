<?php
/********************************************************************************
* disconnect.php : exit page
* ----------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/session.inc.php");
require_once("include/profile.inc.php");
require_once("include/error.inc.php");

$profile=getprofile();
$_SESSION=array();
session_destroy();

require_once("include/header.inc.php");
?>
<div class="title">
  <?php echo _('Déconnexion effectuée !'); ?>
</div>
<p class="normal">
  <?php echo _('Retour au <a href="index.php">profil</a>'); ?>
</p>
<?php
require_once("include/footer.inc.php");
?>

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
require_once($profile['locale']);

$_SESSION=array();
session_destroy();

require_once("include/header.inc.php");
?>
<div class="title">
  <?php echo $locale['disconnect']['title'];?>
</div>
<p class="normal">
  <?php echo $locale['disconnect']['back'];?>
</p>
<?php
require_once("include/footer.inc.php");
?>

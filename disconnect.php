<?php
/********************************************************************************
* disconnect.php : exit page
* ----------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require("include/session.inc.php");
require("include/profile.inc.php");
require("include/error.inc.php");

$profile=getprofile();
require($profile['locale']);

$_SESSION=array();
session_destroy();

require("include/header.inc.php");
?>
<div class="title">
  <?php echo $locale['disconnect']['title'];?>
</div>
<p class="normal">
  <?php echo $locale['disconnect']['back'];?>
</p>
<?php
require("include/footer.inc.php");
?>

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

echo $banana->action_listSubs();

require_once("include/footer.inc.php");
?>

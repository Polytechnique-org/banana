<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

echo $banana->action_listGroups();

require_once("include/footer.inc.php");
?>

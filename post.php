<?php
/********************************************************************************
* post.php : posting page
* ----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

if (isset($_REQUEST['group'])) {
    $group = htmlentities(strtolower($_REQUEST['group']));
}

$id = isset($_REQUEST['id']) ? intval($_REQUEST['id']) : -1;

echo $banana->action_newFup($group, $id);

require_once("include/footer.inc.php");
?>

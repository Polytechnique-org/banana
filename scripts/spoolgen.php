<?php
/********************************************************************************
 * spoolgen.php : spool generation
 * --------------
 *
 * This file is part of the banana distribution
 * Copyright: See COPYING files that comes with this distribution
 ********************************************************************************/

ini_set('max_execution_time','300');
ini_set('include_path','.:..:../platal/include:../../platal/include');

require_once("include/encoding.inc.php");
require_once("include/config.inc.php");
require_once("include/NetNNTP.inc.php");
require_once("include/post.inc.php");
require_once("include/groups.inc.php");
require_once("include/spool.inc.php");
require_once("include/password.inc.php");


$groups = new BananaGroups(2);
$list = array_keys($groups->overview);
unset($groups);
foreach ($list as $g) {
    print "Generating spool for $g : ";
    $spool = new BananaSpool($g);
    print "done.\n";
    unset($spool);
}
$banana->nntp->quit();
?>

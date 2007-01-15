#!/usr/bin/php5
<?php
/********************************************************************************
 * spoolgen.php : spool generation
 * --------------
 *
 * This file is part of the banana distribution
 * Copyright: See COPYING files that comes with this distribution
 ********************************************************************************/

require_once("banana/banana.inc.php");

$opt = getopt('u:p:h');

if(isset($opt['h'])) {
    echo <<<EOF
usage: spoolgen.php [ -u user ] [ -p pass ]
    create all spools, using user user and pass pass
EOF;
    exit;
}

Banana::$nntp_host = "news://{$opt['u']}:{$opt['p']}@localhost:119/\n";
Banana::createAllSpool(array('NNTP'));

// vim:set et sw=4 sts=4 ts=4
?>

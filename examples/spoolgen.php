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

$opt = getopt('u:P:p:hfr:');

if(isset($opt['h'])) {
    echo <<<EOF
usage: spoolgen.php [-h] [-f] [ -u user ] [ -p pass ] [ -P port ] [ -r spool_root ]
    create all spools, using user user and pass pass
    if -f is set, also refresh the RSS feed
EOF;
    exit;
}

if (!isset($opt['P'])) {
    $opt['P'] = '119';
}

Banana::$nntp_host = "news://{$opt['u']}:{$opt['p']}@localhost:{$opt['P']}/\n";
if (isset($opt['r'])) {
    Banana::$spool_root = $opt['r'];
}
if (isset($opt['f'])) {
    Banana::createAllSpool(array('NNTP'));
} else {
    Banana::refreshAllFeeds(array('NNTP'));
}
// vim:set et sw=4 sts=4 ts=4
?>

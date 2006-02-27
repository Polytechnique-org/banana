#! /usr/bin/php4
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
usage: spoolgen.pgp [ -u user ] [ -p pass ]
    create all spools, using user user and pass pass
EOF;
    exit;
}

class MyBanana extends Banana
{
    function MyBanana()
    {
        global $opt;
        $this->host = "http://{$opt['u']}:{$opt['p']}@localhost:119/";
        echo $this->host;
        parent::Banana();
    }

    function createAllSpool()
    {
        $this->_require('groups');
        $this->_require('spool');
        $this->_require('misc');

        $groups = new BananaGroups(BANANA_GROUP_ALL);

        foreach (array_keys($groups->overview) as $g) {
            print "Generating spool for $g : ";
            $spool = new BananaSpool($g);
            print "done.\n";
            unset($spool);
        }
        $this->nntp->quit();
    }
}


$banana = new MyBanana();
$banana->createAllSpool();
?>

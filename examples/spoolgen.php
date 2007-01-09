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

class MyBanana extends Banana
{
    public function __construct()
    {
        global $opt;
        Banana::$host = "news://{$opt['u']}:{$opt['p']}@localhost:119/\n";
        echo Banana::$host;
        parent::__construct();
    }

    private function checkErrors()
    {
        if (Banana::$protocole->lastErrno()) {
            echo "\nL'erreur suivante s'est produite : "
                . Banana::$protocole->lastErrno() . " "
                . Banana::$protocole->lastError() . "\n";
            exit;
        }
    }

    public function createAllSpool()
    {
        $this->checkErrors();
        $groups = Banana::$protocole->getBoxList();
        $this->checkErrors();

        foreach (array_keys($groups) as $g) {
            print "Generating spool for $g : ";
            Banana::$group = $g;
            $spool = $this->loadSpool($g);
            $this->checkErrors();
            print "done.\n";
            unset($spool);
        }
    }
}


$banana = new MyBanana();
$banana->createAllSpool();

// vim:set et sw=4 sts=4 ts=4
?>

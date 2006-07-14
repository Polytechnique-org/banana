<?php

header('Content-Type: image/gif');
passthru('echo ' . escapeshellarg(base64_decode($_REQUEST['face']))
        . '| uncompface -X '
        . '| convert -transparent white xbm:- gif:-');

// vim:set et sw=4 sts=4 ts=4
?>

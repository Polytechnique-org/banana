<?php

passthru('echo '.escapeshellarg(base64_decode($_REQUEST['face'])).'|uncompface -X |convert xbm:- png:-');

?>

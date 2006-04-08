<?php

header('Content-Type: image/jpeg');
passthru('echo '.escapeshellarg(base64_decode(str_replace(' ', '+', $_REQUEST['face']))).'|uncompface -X |convert xbm:- jpg:-');

?>

<?php
/********************************************************************************
* install.d/password.inc.php : NNTP credentials 
* ----------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/
$news['server']="localhost:119";
$news["user"]=$_SESSION['login'];
$news["pass"]=$_SESSION['passwd'];
?>

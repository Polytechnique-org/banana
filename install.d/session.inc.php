<?php
/********************************************************************************
* include/session.inc.php : sessions for profile
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

session_start();

// verify if a profile has been submitted
if (!isset($_SESSION['profile']) && isset($_POST['action'] && $_POST['action']!="OK")) {
  $_SESSION['name'] = $_POST['profile_name'];
  $_SESSION['mail'] = $_POST['profile_mail'];
  $_SESSION['org'] = $_POST['profile_org'];
  $_SESSION['sig'] = $_POST['profile_sig'];
  $_SESSION['login'] = $_POST['profile_login'];
  $_SESSION['passwd'] = $_POST['profile_passwd'];
  $_SESSION['displaytype'] = $_POST['displaytype'];
  $_SESSION['profile'] = true;
}

//sets sessions variables
if (!isset($_SESSION['profile'])) {
  require("include/profile.inc.php");
  $profile=getprofile();
  require($profile['locale']);
  require("header.inc.php");
  require("profile_form.inc.php");
  require("footer.inc.php");
  exit;
}

// refresh-post protection
$sname = $_SERVER['SCRIPT_NAME'];
$array = explode('/',$sname);
$sname = array_pop($array);
unset($array);
switch ($sname) {
  case "thread.php":
    if (!isset($_SESSION['bananapostok'])) 
      $_SESSION['bananapostok']=true;
    break;
  default:
    $_SESSION['bananapostok']=true;
    break;
}
?>

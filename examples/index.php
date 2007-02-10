<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("banana/banana.inc.php");

session_start();

class MyBanana extends Banana
{
    protected function action_saveSubs($groups)
    {
        parent::action_saveSubs($groups);
        setcookie('banana_subs', serialize(Banana::$profile['subscribe']));
        return true;
    }
}

if (isset($_COOKIE['banana_subs'])) {
    Banana::$profile['subscribe'] = unserialize($_COOKIE['banana_subs']);
}
if (!isset($_SESSION['banana_lastnews']) && isset($_COOKIE['banana_lastnews'])) {
    $_SESSION['banana_lastnews'] = $_COOKIE['banana_lastnews'];
}
if (isset($_SESSION['banana_lastnews'])) {
    Banana::$profile['lastnews'] = $_SESSION['banana_lastnews'];
}
setcookie('banana_lastnews', time());

$banana = new MyBanana();
$res = $banana->run();

session_write_close();

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="description" content="WebForum2/Banana">
    <link href="css/style.css" type="text/css" rel="stylesheet" media="screen">
    <link href="css/banana.css" type="text/css" rel="stylesheet" media="screen">
    <title>
      Banana, a NNTP<->Web Gateway 
    </title>
  </head>
  <body>
    <div class="bloc">
      <h1>Les Forums de Banana</h1>
      <?php echo $res; ?>
      <div class="foot">
        <em>Banana</em>, a Web interface for a NNTP Server<br />
        Developed under GPL License for <a href="http://www.polytechnique.org">Polytechnique.org</a>
        Use <em>silk</em> icons from <a href="http://www.famfamfam.com/lab/icons/silk/">www.famfamfam.com</a>
      </div>
    </div>
  </body>
</html>
<?php

// vim:set et sw=4 sts=4 ts=4
?>

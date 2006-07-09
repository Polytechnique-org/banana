<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("banana/banana.inc.php");
$res = Banana::run();

if ($res != "") {
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
  <head>
    <meta http-equiv="Content-Type" content="text/html; 
    charset=iso-8859-1">
    <meta name="description" content="WebForum2/Banana">
    <link href="css/style.css" type="text/css" rel="stylesheet" media="screen">
    <link href="css/banana.css" type="text/css" rel="stylesheet" media="screen">
    <title>
      Banana, a NNTP<->Web Gateway 
    </title>
  </head>
  <body>
    <div class="bloc">
<?php echo $res; ?>
      <div class="foot">
        <em>Banana</em>, a Web interface for a NNTP Server<br />
        Developed under GPL License for <a href="http://www.polytechnique.org">Polytechnique.org</a>
      </div>
    </div>
  </body>
</html>
<?php
}

// vim:set et sw=4 sts=4 ts=4
?>

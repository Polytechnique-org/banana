<?php
/********************************************************************************
* index.php : main page (newsgroups list)
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("banana/banana.inc.php");
$banana = new Banana();
$res = $banana->run();

if ($res != "") {
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; 
    charset=UTF-8">
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
        Use <em>silk</em> icons from <a href="http://www.famfamfam.com/lab/icons/silk/">www.famfamfam.com</a>
      </div>
    </div>
  </body>
</html>
<?php
}

// vim:set et sw=4 sts=4 ts=4
?>

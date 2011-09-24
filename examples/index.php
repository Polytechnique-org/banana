<?php
/********************************************************************************
* index.php : Banana NNTP client example
* -----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("banana/banana.inc.php");

session_start();

// Some configuration
Banana::$nntp_host  = 'news://user:password@host:119/'; // where is the news server
Banana::$spool_root  = dirname(__FILE__) . '/spool'; // where to store cache files
Banana::$debug_nntp   = false; // if true, show the NNTP backtrace
Banana::$debug_smarty = false; // if true, shos php-error in page generation
Banana::$feed_active  = true;  // Activate RSS feed
Banana::$feed_updateOnDemand = true; // Update the feed cache when it is acceeded

// Implement a Banana which stores subscription list in a cookie
class MyBanana extends Banana
{
    protected function action_saveSubs($groups)
    {
        parent::action_saveSubs($groups);
        setcookie('banana_subs', serialize(Banana::$profile['subscribe']), time() + 25920000);
        return true;
    }
}

// Implements storage of a list of read messages
// (this is only an example of what is possible)
function hook_listReadMessages($group)
{
    if (!isset($_COOKIE['banana_read'])) {
        return null;
    }
    $msgs = unserialize(gzinflate(base64_decode($_COOKIE['banana_read'])));
    return array_keys($msgs);
}

function hook_markAsRead($group, $artid, $msg)
{
    $msgs = array();
    if (isset($_COOKIE['banana_read'])) {
        $msgs = unserialize(gzinflate(base64_decode($_COOKIE['banana_read'])));
    }
    $id = $msg->getHeader('message-id');
    $msgs[$id] = true;
    $msgs = base64_encode(gzdeflate(serialize($msgs)));
    setcookie('banana_read', $msgs, 0);
    $_COOKIE['banana_read'] = $msgs;
}


// Minimalist login
if ((@$_GET['action'] == 'rss2') ||
    (!isset($_SESSION['banana_email']) || isset($_POST['change_login']) || isset($_POST['valid_change']))) {
    if (isset($_COOKIE['banana_email']) && !isset($_POST['change_login']) && !isset($_POST['valid_change'])) {
        $_SESSION['banana_email'] = $_COOKIE['banana_email'];
        $_SESSION['banana_name'] = $_COOKIE['banana_name'];
    } elseif (isset($_POST['valid_change'])) {
        $_SESSION['banana_name'] = $_POST['name'];
        $_SESSION['banana_email'] = $_POST['email'];
        setcookie('banana_name', $_POST['name'],  time() + 25920000);
        setcookie('banana_email', $_POST['email'],  time() + 25920000);
    } else {
?>
<html xmlns="http://www.w3.org/1999/xhtml"> 
  <head> 
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" /> 
    <meta name="description" content="WebForum2/Banana" /> 
    <link href="css/style.css" type="text/css" rel="stylesheet" media="screen" /> 
    <link href="css/banana.css" type="text/css" rel="stylesheet" media="screen"> 
    <title>Banana, a NNTP<->Web Gateway</title>
  </head>
  <body>
    <div class="bloc">
      <h1>Les Forums de Banana</h1>
      Merci d'entrer vos identifiants pour accéder à Banana :
      <form action="" method="post">
        <div class="banana" style="margin: auto; width: 50%">
          Nom : <input type="text" name="name" size="40" /><br />
          Email : <input type="text" name="email" size="40" />
          <div class="center">
            <input type="submit" name="valid_change" value="Valider" />
          </div>
        </div>
      </form>
      <div class="foot">
        <em>Banana</em>, a Web interface for a NNTP Server<br /> 
        Developed under GPL License for <a href="http://www.polytechnique.org">Polytechnique.org</a><br />
        Use <em>silk</em> icons from <a href="http://www.famfamfam.com/lab/icons/silk/">www.famfamfam.com</a> 
      </div>
    </div>
  </body>
</html>
<?php
        exit;
    }
}

// Restore subscription list
if (isset($_COOKIE['banana_subs'])) {
    Banana::$profile['subscribe'] = unserialize($_COOKIE['banana_subs']);
}

// Compute and set last visit time
if (!isset($_SESSION['banana_lastnews']) && isset($_COOKIE['banana_lastnews'])) {
    $_SESSION['banana_lastnews'] = $_COOKIE['banana_lastnews'];
} else if (!isset($_SESSION['banana_lastnews'])) {
    $_SESSION['banana_lastnews'] = 0;
}
Banana::$profile['signature'] = $_SESSION['banana_name'];
Banana::$profile['headers']['From'] = '"' . $_SESSION['banana_name'] . '" <' . $_SESSION['banana_email'] . '>';
Banana::$profile['lastnews'] = $_SESSION['banana_lastnews'];
setcookie('banana_lastnews', time(),  time() + 25920000);

// Run Bananan
$banana = new MyBanana();    // Create the instance of Banana
$res  = $banana->run();       // Run banana, and generate the XHTML output
$css  = $banana->css();       // Get the CSS code to add in my page headers
$feed = $banana->feed();      // Get a link to banana's feed. You need to use Banana::refreshAllFeeds in a cron or enable Banana::$feed_updateOnDemand in order to keep up-to-date feeds
$bt   = $banana->backtrace(); // Get protocole execution backtrace

session_write_close();
if (@strtolower($_GET['output']) === 'json') {
    header('Content-Type: text/javascript');
    echo $res;
    exit;
}

// Genererate the page
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
  <head>
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
    <meta name="description" content="WebForum2/Banana" />
    <link href="css/style.css" type="text/css" rel="stylesheet" media="screen" />
    <link href="css/banana.css" type="text/css" rel="stylesheet" media="screen" />
<?php if ($feed) { ?>
    <link rel="alternate" type="application/rss+xml" title="Banana :: Abonnements" href="<?php echo htmlentities($feed); ?>" />
<?php } ?>
<?php if ($css) { ?>
    <style type="text/css">
        <?php echo $css; ?>
    </style>
<?php } ?>
    <title>
      Banana, a NNTP<->Web Gateway 
    </title>
  </head>
  <body>
    <div class="bloc">
      <h1>Les Forums de Banana</h1>
      <?php echo $res; ?>
      <div>
      <div style="padding-top: 1ex; float: right; text-align: right; font-size: small"> 
        <form action="" method="post">
        Vous êtes :<br /> 
        <?php echo $_SESSION['banana_name'] . ' &lt;' . $_SESSION['banana_email'] . '&gt;'; ?><br /> 
        <input type="submit" name="change_login" value="Changer" />
        </form>
      </div> 
      <div class="foot">
        <em>Banana</em>, a Web interface for a NNTP Server<br />
        Developed under GPL License for <a href="http://www.polytechnique.org">Polytechnique.org</a><br />
        Use <em>silk</em> icons from <a href="http://www.famfamfam.com/lab/icons/silk/">www.famfamfam.com</a>
      </div>
      </div>
<?php
    // Generate the protocole Backtrace at the bottom of the page
    if ($bt) {
        echo "<div class=\"backtrace\">";
        foreach ($bt as &$entry) {
            echo "<div><pre>" . $entry['action'] . "</pre>";
            echo "<p style=\"padding-left: 4em;\">"
                 . "Exécution en " . sprintf("%.3fs", $entry['time']) . "<br />"
                 . "Retour : " . $entry['code'] . "<br />"
                 . "Lignes : " . $entry['response'] . "</p></div>";
        }
        echo "</div>";
    }
?>
    </div>
  </body>
</html>
<?php

// vim:set et sw=4 sts=4 ts=4
?>

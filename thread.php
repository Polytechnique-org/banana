<?php
/********************************************************************************
* thread.php : group overview
* ------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require("locales/locales.inc.php");
require("include/session.inc.php");
require("include/encoding.inc.php");
require("include/format.inc.php");
require("include/config.inc.php");
require("include/NetNNTP.inc.php");
include("include/post.inc.php");
require("include/spool.inc.php");
require("include/password.inc.php");
require("include/profile.inc.php");
include("include/wrapper.inc.php");

require("include/header.inc.php");

$profile = getprofile();

if (isset($_REQUEST['group'])) {
  $group=htmlentities(strtolower($_REQUEST['group']));
} else {
  $group=htmlentities(strtolower(strtok(str_replace(" ","",$_REQUEST['newsgroups']),",")));
}
$id=htmlentities(strtolower($_REQUEST['id']));

//$mynntp = new nntp($news['server'],120,1);
$mynntp = new nntp($news['server']);
if (!$mynntp) {
  echo "<p class=\"error\">\n\t".$locale['error']['connect']."\n</p>";
  require("include/footer.inc.php");
  exit;
}

if ($news['user']!="anonymous") {
  $result = $mynntp->authinfo($news["user"],$news["pass"]);
  if (!$result) {
    echo "<p class=\"error\">\n\t".$locale['error']['credentials']
      ."\n</p>";
    require("include/footer.inc.php");
    exit;
  }
}
$spool = new spool($mynntp,$group,$profile['display'],
  $profile['lastnews']);
if (!$spool) {
  echo "<p class=\"error\">\n\t".$locale['error']['group']."\n</p>";
  require("footer.inc.php");
  exit;
}
$max = 50;
if ($_REQUEST['first']>sizeof($spool->overview))
  $_REQUEST['first']=sizeof($spool->overview);
$first = (isset($_REQUEST['first'])?
  (floor($_REQUEST['first']/$max)*$max+1):1);
$last  = (isset($_REQUEST['first'])?
  (floor($_REQUEST['first']/$max+1)*$max):$max);

if (isset($_REQUEST['action']) && (isset($_REQUEST['type']))) {
  switch ($_REQUEST['type']) {  
    case 'cancel':
      $mid = array_search($id,$spool->ids);
      $mynntp->group($group);
      $post = new post($mynntp,$id);
      
      if (checkcancel($post->headers)) {
        $message = 'From: '.$profile['name']."\n"
          ."Newsgroups: $group\n"
          ."Subject: cmsg $mid\n"
          .$news['customhdr']
          ."Control: cancel $mid\n"
          ."\n"
          ."Message canceled with Banana";
        $result = $mynntp->post($message);
        if ($result) {
          $spool->delid($id);
          $text="<p class=\"normal\">".$locale['post']['canceled']
            ."</p>";
        } else {
          $text="<p class=\"error\">".$locale['post']['badcancel']
            ."</p>";
        }
      } else {
        $text="<p class=\"error\">\n\t".$locale['post']['rghtcancel']
          ."\n</p>";
      }
      break;
    case 'new':
      $message = 'From: '.$profile['name']."\n"
        ."Newsgroups: ".stripslashes(str_replace(" ","",
          $_REQUEST['newsgroups']))."\n"
        ."Subject: ".stripslashes($_REQUEST['subject'])."\n"
        .($_REQUEST['followup']!=''?'Followup-To: '
        .stripslashes($_REQUEST['followup'])."\n":"")
        .$news['customhdr']
        ."\n"
        .wrap(stripslashes($_REQUEST['body']),"",$news['wrap']);
      $result = $mynntp->post($message);
      if ($result) {
        $text="<p class=\"normal\">".$locale['post']['posted']."</p>";
      } else {
        $text="<p class=\"error\">".$locale['post']['badpost']."</p>";
      }
      break;
    case 'followupok':
      $rq=$mynntp->group($group);
      $post = new post($mynntp,$id);
      if ($post) {
        $refs = $post->headers->references." ".$post->headers->msgid;
      }
    
      $message = 'From: '.$profile['name']."\n"
        ."Newsgroups: ".stripslashes($_REQUEST['newsgroups'])."\n"
        ."Subject: ".stripslashes($_REQUEST['subject'])."\n"
        .($_REQUEST['followup']!=''?'Followup-To: '
        .stripslashes($_REQUEST['followup'])."\n":"")
        ."References: $refs\n"
        .$news['customhdr']
        ."\n"
        .wrap(stripslashes($_REQUEST['body']),"",$news['wrap']);
      $result = $mynntp->post($message);
      if ($result) {
        $text="<p class=\"normal\">".$locale['post']['posted']."</p>";
      } else {
        $text="<p class=\"error\">".$locale['post']['badpost']."</p>";
      }
      break;
  }
  $spool = new spool($mynntp,$group,$profile['display'],
    $profile['lastnews']);
  if (!$spool) {
    echo "<p class=\"error\">\n\t".$locale['error']['group']."\n</p>";
    require("include/footer.inc.php");
    exit;
  }
}


?>
<div class="title">
  <?php echo $locale['thread']['group_b'].$group
    .$locale['thread']['group_a'];?>
</div>
<?php
echo $text;
displayshortcuts();

?>

<table class="bicol" cellpadding="0" cellspacing="0" border="0" 
  summary="<?php echo $locale['thread']['summary'];?>">
  <tr>
    <th class="date">
      <?php echo $locale['thread']['date'];?>
    </th>
    <th class="subject">
      <?php echo $locale['thread']['subject'];?>
    </th>
    <th class="from">
      <?php echo $locale['thread']['author'];?>
    </th>
  </tr>
<?php
$spool->disp($first,$last);
$mynntp->quit();
echo "</table>";

displayshortcuts();

require("include/footer.inc.php");
?>

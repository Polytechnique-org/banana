<?php
/********************************************************************************
* thread.php : group overview
* ------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

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
include("include/error.inc.php");

$profile=getprofile();
require($profile['locale']);

require("include/header.inc.php");

if (isset($_REQUEST['group'])) {
  $group=htmlentities(strtolower($_REQUEST['group']));
} else {
  $group=htmlentities(strtolower(strtok(str_replace(" ","",$_REQUEST['newsgroups']),",")));
}

if (isset($_REQUEST['id'])) {
  $id=htmlentities(strtolower($_REQUEST['id']));
}

//$nntp = new nntp($news['server'],120,1);
$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
  $result = $nntp->authinfo($news["user"],$news["pass"]);
  if (!$result) error("nntpauth");
}
$spool = new spool($nntp,$group,$profile['display'],
  $profile['lastnews']);
if (!$spool) error("nntpspool");
$max = 50;
if (isset($_REQUEST['first']) && ($_REQUEST['first']>sizeof($spool->overview)))
  $_REQUEST['first']=sizeof($spool->overview);
$first = (isset($_REQUEST['first'])?
  (floor($_REQUEST['first']/$max)*$max+1):1);
$last  = (isset($_REQUEST['first'])?
  (floor($_REQUEST['first']/$max+1)*$max):$max);

if (isset($_REQUEST['action']) && (isset($_REQUEST['type'])) && 
(isset($_SESSION['bananapostok'])) && ($_SESSION['bananapostok'])) {
  switch ($_REQUEST['type']) {  
    case 'cancel':
      $mid = array_search($id,$spool->ids);
      $nntp->group($group);
      $post = new post($nntp,$id);
      
      if (checkcancel($post->headers)) {
        $message = 'From: '.$profile['name']."\n"
          ."Newsgroups: $group\n"
          ."Subject: cmsg $mid\n"
          .$news['customhdr']
          ."Control: cancel $mid\n"
          ."\n"
          ."Message canceled with Banana";
        $result = $nntp->post($message);
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
      $body = preg_replace("/\n\.[ \t\r]*\n/m","\n..\n",stripslashes($_REQUEST['body']));
      $message = 'From: '.$profile['name']."\n"
        ."Newsgroups: ".stripslashes(str_replace(" ","",
          $_REQUEST['newsgroups']))."\n"
        ."Subject: ".stripslashes($_REQUEST['subject'])."\n"
        .(isset($profile['org'])?"Organization: ".$profile['org']."\n":"")
        .($_REQUEST['followup']!=''?'Followup-To: '
        .stripslashes($_REQUEST['followup'])."\n":"")
        .$news['customhdr']
        ."\n"
        .wrap($body,"",$news['wrap']);
      $result = $nntp->post($message);
      if ($result) {
        $text="<p class=\"normal\">".$locale['post']['posted']."</p>";
      } else {
        $text="<p class=\"error\">".$locale['post']['badpost']."</p>";
      }
      break;
    case 'followupok':
      $rq=$nntp->group($group);
      $post = new post($nntp,$id);
      if ($post) {
        $refs = (isset($post->headers->references)?
                $post->headers->references." ":"").$post->headers->msgid;
      }
    
      $body = preg_replace("/\n\.[ \t\r]*\n/m","\n..\n",stripslashes($_REQUEST['body']));
      $message = 'From: '.$profile['name']."\n"
        ."Newsgroups: ".stripslashes($_REQUEST['newsgroups'])."\n"
        ."Subject: ".stripslashes($_REQUEST['subject'])."\n"
        .(isset($profile['org'])?"Organization: ".$profile['org']."\n":"")
        .($_REQUEST['followup']!=''?'Followup-To: '
        .stripslashes($_REQUEST['followup'])."\n":"")
        ."References: $refs\n"
        .$news['customhdr']
        .$profile['customhdr']
        ."\n"
        .wrap($body,"",$news['wrap']);
      $result = $nntp->post($message);
      if ($result) {
        $text="<p class=\"normal\">".$locale['post']['posted']."</p>";
      } else {
        $text="<p class=\"error\">".$locale['post']['badpost']."</p>";
      }
      break;
  }
  $_SESSION['bananapostok']=false;
  $spool = new spool($nntp,$group,$profile['display'],
    $profile['lastnews']);
  if (!$spool) error("nntpspool");
}


?>
<h1>
  <?php echo $locale['thread']['group_b'].$group
    .$locale['thread']['group_a'];?>
</h1>
<?php
if (isset($text)) {
    echo $text;
}
displayshortcuts();

?>

<table class="<?php echo $css['bicol']?>" cellpadding="0" cellspacing="0" border="0" 
  summary="<?php echo $locale['thread']['summary'];?>">
  <tr>
    <th class="<?php echo $css['date']?>">
      <?php echo $locale['thread']['date'];?>
    </th>
    <th class="<?php echo $css['subject']?>">
      <?php echo $locale['thread']['subject'];?>
    </th>
    <th class="<?php echo $css['from']?>">
      <?php echo $locale['thread']['author'];?>
    </th>
  </tr>
<?php
$spool->disp($first,$last);
$nntp->quit();
echo "</table>";

displayshortcuts();

require("include/footer.inc.php");
?>

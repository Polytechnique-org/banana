<?php
/********************************************************************************
* thread.php : group overview
* ------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

if (isset($_REQUEST['group'])) {
    $group = htmlentities(strtolower($_REQUEST['group']));
} else {
    $group = htmlentities(strtolower(strtok(str_replace(" ","",$_REQUEST['newsgroups']),",")));
}

if (isset($_REQUEST['id'])) {
    $id=htmlentities(strtolower($_REQUEST['id']));
}

$banana->newSpool($group, $banana->profile['display'], $banana->profile['lastnews']);
$max = $banana->tmax;
if (isset($_REQUEST['first']) && ($_REQUEST['first']>sizeof($banana->spool->overview))) {
    $_REQUEST['first'] = sizeof($banana->spool->overview);
}
$first = (isset($_REQUEST['first']) ? (floor($_REQUEST['first']/$max)*$max+1) : 1);
$last  = (isset($_REQUEST['first']) ? (floor($_REQUEST['first']/$max+1)*$max) : $max);

if (isset($_REQUEST['action']) && (isset($_REQUEST['type']))
        && (isset($_SESSION['bananapostok'])) && ($_SESSION['bananapostok']))
{
    switch ($_REQUEST['type']) {  
        case 'cancel':
            $banana->nntp->group($group);
            $mid  = array_search($id, $banana->spool->ids);
            $banana->newPost($id);

            if ($banana->post && $banana->post->checkcancel()) {
                $message = 'From: '.$banana->profile['name']."\n"
                    ."Newsgroups: $group\n"
                    ."Subject: cmsg $mid\n"
                    .$banana->custom
                    ."Control: cancel $mid\n"
                    ."\n"
                    ."Message canceled with Banana";
                if ($banana->nntp->post($message)) {
                    $banana->spool->delid($id);
                    $text = "<p class=\"normal\">"._b_('Message annulé')."</p>";
                } else {
                    $text = "<p class=\"error\">"._b_('Impossible d\'annuler le message')."</p>";
                }
            } else {
                $text = "<p class=\"error\">\n\t"._b_('Vous n\'avez pas les permissions pour annuler ce message')."\n</p>";
            }
            break;

        case 'new':
            $body = preg_replace("/\n\.[ \t\r]*\n/m","\n..\n",$_REQUEST['body']);
            $message = 'From: '.$banana->profile['name']."\n"
                ."Newsgroups: ".str_replace(" ","", $_REQUEST['newsgroups'])."\n"
                ."Subject: ".$_REQUEST['subject']."\n"
                .(isset($banana->profile['org'])?"Organization: ".$banana->profile['org']."\n":"")
                .($_REQUEST['followup']!=''?'Followup-To: '.$_REQUEST['followup']."\n":"")
                .$banana->custom
                ."\n"
                .wrap($body, "", $banana->wrap);
            if ($banana->nntp->post($message)) {
                $text = "<p class=\"normal\">"._b_('Message posté')."</p>";
            } else {
                $text = "<p class=\"error\">"._b_('Impossible de poster le message')."</p>";
            }
            break;

        case 'followupok':
            $rq=$banana->nntp->group($group);
            $banana->newPost($id);
            if ($banana->post) {
                $refs = (isset($banana->post->headers['references'])?
                $banana->post->headers['references']." ":"").$banana->post->headers['message-id'];
            }

            $body = preg_replace("/\n\.[ \t\r]*\n/m","\n..\n",$_REQUEST['body']);
            $message = 'From: '.$banana->profile['name']."\n"
                ."Newsgroups: ".$_REQUEST['newsgroups']."\n"
                ."Subject: ".$_REQUEST['subject']."\n"
                .(isset($banana->profile['org'])?"Organization: ".$banana->profile['org']."\n":"")
                .($_REQUEST['followup']!=''?'Followup-To: '.$_REQUEST['followup']."\n":"")
                ."References: $refs\n"
                .$banana->custom
                .$banana->profile['customhdr']
                ."\n"
                .wrap($body, "", $banana->wrap);
            if ($banana->nntp->post($message)) {
                $text = "<p class=\"normal\">"._b_('Message posté')."</p>";
            } else {
                $text = "<p class=\"error\">"._b_('Impossible de poster le message')."</p>";
            }
            break;
    }
    $_SESSION['bananapostok']=false;
    $banana->newSpool($group, $banana->profile['display'], $banana->profile['lastnews']);
}

?>
<h1><?php echo $group; ?></h1>
<?php
if (isset($text)) { echo $text; }
displayshortcuts($first);

?>

<table class="<?php echo $css['bicol']?>" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <th class="<?php echo $css['date']?>">
      <?php echo _b_('Date'); ?>
    </th>
    <th class="<?php echo $css['subject']?>">
      <?php echo _b_('Sujet'); ?>
    </th>
    <th class="<?php echo $css['from']?>">
      <?php echo _b_('Auteur'); ?>
    </th>
  </tr>
<?php
$banana->spool->disp($first, $last);
$banana->nntp->quit();
echo "</table>";

displayshortcuts($first);

require_once("include/footer.inc.php");
?>

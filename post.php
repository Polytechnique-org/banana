<?php
/********************************************************************************
* post.php : posting page
* ----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/session.inc.php");
require_once("include/misc.inc.php");
require_once("include/format.inc.php");
require_once("include/config.inc.php");
require_once("include/NetNNTP.inc.php");
require_once("include/post.inc.php");
require_once("include/spool.inc.php");
require_once("include/password.inc.php");
require_once("include/profile.inc.php");
require_once("include/error.inc.php");

$profile = getprofile();
require_once("include/header.inc.php");
if (isset($_REQUEST['group'])) {
    $group=htmlentities(strtolower($_REQUEST['group']));
}
if (isset($_REQUEST['id'])) {
    $id=htmlentities(strtolower($_REQUEST['id']));
}

if (isset($group)) {
    $target = $group;
}

$nntp = new nntp($news['server']);
if (!$nntp) error("nntpsock");
if ($news['user']!="anonymous") {
    $result = $nntp->authinfo($news["user"],$news["pass"]);
    if (!$result) error("nntpauth");
}

if (isset($group) && isset($id) && isset($_REQUEST['type']) && 
        ($_REQUEST['type']=='followup')) {
    $rq=$nntp->group($group);
    $post = new BananaPost($nntp,$id);
    if ($post) {
        $subject = (preg_match("/^re:/i",$post->headers['subject'])?"":"Re: ").$post->headers['subject'];
        if ($profile['dropsig']) {
            $cutoff=strpos($post->body,"\n-- \n");
            if ($cutoff) {
                $quotetext = substr($post->body,0,strpos($post->body,"\n-- \n"));
            } else {
                $quotetext = $post->body;
            }
        } else {
            $quotetext = $post->body;
        }
        $body = $post->name." wrote :\n".wrap($quotetext, "> ");
        if (isset($post->headers['followup-to']))
            $target = $post->headers['followup-to'];
        else
            $target = $post->headers['newsgroups'];
    }
}

$nntp->quit();
?>
<h1>
  <?php echo _b_('Nouveau message'); ?>
</h1>
<?php

displayshortcuts();

?>

<form action="thread.php" method="post">
<table class="<?php echo $css['bicol']?>" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <th colspan="2">
      <?php echo _b_('En-têtes'); ?>
    </th>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Nom'); ?>
    </td>
    <td>
      <?php echo htmlentities($profile['name']); ?>
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Sujet'); ?>
    </td>
    <td>
      <input type="text" name="subject" value="<?php echo 
        (isset($subject)?$subject:"");?>" />
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Forums'); ?>
    </td>
    <td>
      <input type="text" name="newsgroups" value="<?php echo
      (isset($target)?$target:"");?>" />
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Suivi-à'); ?>
    </td>
    <td>
      <input type="text" name="followup" value="" />
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Organisation'); ?>
    </td>
    <td>
      <?php echo $profile['org']; ?>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo _b_('Corps'); ?>
    </th>
  </tr>
  <tr>
    <td class="<?php echo $css['bicolvpadd'];?>" colspan="2">
      <textarea name="body" cols="90" rows="16"><?php echo 
      (isset($body)?htmlentities($body):"").
      ($profile['sig']!=''?"\n\n-- \n".htmlentities($profile['sig']):"");?></textarea>
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bouton']?>" colspan="2">
<?php
if (isset($group) && isset($id) && isset($_REQUEST['type']) 
  && ($_REQUEST['type']=='followup')) {
?>
      <input type="hidden" name="type" value="followupok" />
      <input type="hidden" name="group" value="<?php echo $group;?>" />
      <input type="hidden" name="id" value="<?php echo $id;?>" />
<?php
} else {
?>
      <input type="hidden" name="type" value="new" />
<?php
}
?>
      <input type="submit" name="action" value="OK" />
    </td>
  </tr>
</table>
</form>
<?php
displayshortcuts();
require_once("include/footer.inc.php");
?>

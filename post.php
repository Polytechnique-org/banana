<?php
/********************************************************************************
* post.php : posting page
* ----------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once("include/banana.inc.php");
require_once("include/header.inc.php");

if (isset($_REQUEST['group'])) {
    $group = htmlentities(strtolower($_REQUEST['group']));
}
if (isset($_REQUEST['id'])) {
    $id = htmlentities(strtolower($_REQUEST['id']));
}

if (isset($group)) {
    $target = $group;
}

if (isset($group) && isset($id) && isset($_REQUEST['type']) && ($_REQUEST['type']=='followup')) {
    $rq   = $banana->nntp->group($group);
    $banana->newPost($id);
    $body = '';
    if ($banana->post) {
        $subject = (preg_match("/^re\s*:\s*/i", $banana->post->headers['subject']) ? '' : 'Re: ').$banana->post->headers['subject'];
        $body    = $banana->post->name." wrote :\n".wrap($banana->post->body, "> ");
        $target  = isset($banana->post->headers['followup-to']) ? $banana->post->headers['followup-to'] : $banana->post->headers['newsgroups'];
    }
}

$banana->nntp->quit();
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
      <?php echo htmlentities($banana->profile['name']); ?>
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Sujet'); ?>
    </td>
    <td>
      <input type="text" name="subject" value="<?php if (isset($subject)) echo $subject; ?>" />
    </td>
  </tr>
  <tr>
    <td class="<?php echo $css['bicoltitre'];?>">
      <?php echo _b_('Forums'); ?>
    </td>
    <td>
      <input type="text" name="newsgroups" value="<?php if (isset($target)) echo $target; ?>" />
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
      <?php echo $banana->profile['org']; ?>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo _b_('Corps'); ?>
    </th>
  </tr>
  <tr>
    <td class="<?php echo $css['bicolvpadd'];?>" colspan="2">
      <textarea name="body" cols="90" rows="16"><?php
      echo htmlentities($body);
      if ($banana->profile['sig']) echo "\n\n-- \n".htmlentities($banana->profile['sig']);
      ?></textarea>
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

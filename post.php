<?php
require("locales/locales.inc.php");
require("include/session.inc.php");
require("include/encoding.inc.php");
require("include/format.inc.php");
require("include/config.inc.php");
require("include/NetNNTP.inc.php");
require("include/post.inc.php");
require("include/spool.inc.php");
require("include/password.inc.php");
require("include/profile.inc.php");
require("include/wrapper.inc.php");

require("include/header.inc.php");
$profile = getprofile();
$group=htmlentities(strtolower($_REQUEST['group']));
$id=htmlentities(strtolower($_REQUEST['id']));

if (isset($group)) {
  $target = $group;
}

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

if (isset($group) && isset($id) && isset($_REQUEST['type']) && 
  ($_REQUEST['type']=='followup')) {
  $rq=$mynntp->group($group);
  $post = new post($mynntp,$id);
  if ($post) {
    $subject = (preg_match("/^re:/i",$post->headers->subject)?"":"Re: ")
      .$post->headers->subject;
    $body = $post->headers->name." wrote :\n".wrap($post->body, ">");
    if (isset($post->headers->followup))
      $target=$post->headers->followup;
    else
      $target=$post->headers->newsgroups;
  }
}

$mynntp->quit();
?>
<div class="title">
  <?php echo $locale['post']['title'];?>
</div>
<?php

displayshortcuts();

?>

<form action="thread.php" method="POST">
<table class="bicol" cellpadding="0" cellspacing="0" border="0">
  <tr>
    <th colspan="2">
      <?php echo $locale['post']['headers'];?>
    </th>
  </tr>
  <tr>
    <td>
      <?php echo $locale['post']['name'];?>
    </td>
    <td>
      <?php echo htmlentities($profile['name']); ?>
    </td>
  </tr>
  <tr>
    <td>
      <?php echo $locale['post']['subject'];?>
    </td>
    <td>
      <input type="text" name="subject" value="<?php echo 
        (isset($subject)?$subject:"");?>" />
    </td>
  </tr>
  <tr>
    <td>
      <?php echo $locale['post']['newsgroups'];?>
    </td>
    <td>
      <input type="text" name="newsgroups" value="<?php echo
      (isset($target)?$target:"");?>" />
    </td>
  </tr>
  <tr>
    <td>
      <?php echo $locale['post']['fu2'];?>
    </td>
    <td>
      <input type="text" name="followup" value="" />
    </td>
  </tr>
  <tr>
    <td>
      <?php echo $locale['post']['organization'];?>
    </td>
    <td>
      <?php echo $profile['org']; ?>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo $locale['post']['body'];?>
    </th>
  </tr>
  <tr>
    <td colspan="2">
      <textarea name="body" cols="90" rows="10"><?php echo 
      (isset($body)?$body:"").($profile['sig']!=''?"\n\n-- \n"
      .$profile['sig']:"");?></textarea>
    </td>
  </tr>
  <tr>
    <td class="bouton" colspan="2">
<?php
if (isset($group) && isset($id) && isset($_REQUEST['type']) 
  && ($_REQUEST['type']=='followup')) {
?>
      <input type="hidden" name="type" value="followupok" />
      <input type="hidden" name="group" value="<?php echo $group;?>" />
      <input type="hidden" name="id" value="<?php echo $id;?> " />
<?php
} else {
?>
      <input type="hidden" name="type" value="new" />
<?php
}
?>
      <input type="submit" name="action" value="OK" />
    </td>
</table>
</form>
<?php
displayshortcuts();
require("include/footer.inc.php");
?>

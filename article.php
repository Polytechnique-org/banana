<?php
/********************************************************************************
* article.php : article page
* -------------
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

$banana->newSpool($group, $banana->profile['display'], $banana->profile['lastnews']);
$banana->nntp->group($group);

$post = new BananaPost($id);
if (!$post) {
    if ($banana->nntp->lasterrorcode == "423") {
        $banana->spool->delid($id);
    }
    error("nntpart");
}

$ndx = $banana->spool->getndx($id);

?>
<h1><?php echo _b_('Message'); ?></h1>

<?php
if (isset($_GET['type']) && ($_GET['type']=='cancel') && (checkcancel($post->headers))) {
?>
<p class="<?php echo $css['error']?>">
  <?php echo _b_('Voulez-vous vraiment annuler ce message ?'); ?>
</p>
<form action="thread.php" method="post">
  <input type="hidden" name="group" value="<?php echo $group; ?>" />
  <input type="hidden" name="id" value="<?php  echo $id; ?>" />
  <input type="hidden" name="type" value="cancel" />
  <input type="submit" name="action" value="<?php echo _b_('OK'); ?>" />
</form>
<?
}

displayshortcuts();
?>

<table class="<?php echo $css['bicol']?>" cellpadding="0" cellspacing="0" 
summary="<?php echo _b_('Contenu du message'); ?>">
  <tr>
    <th colspan="2">
      <?php echo _b_('En-têtes'); ?>
    </th>
  </tr>
<?php
    foreach ($banana->show_hdr as $hdr) {
        if (isset($post->headers[$hdr])) {
            $res = formatdisplayheader($hdr, $post->headers[$hdr]);
            if ($res)
                echo "<tr><td class=\"{$css['bicoltitre']}\">".header_translate($hdr)."</td>"
                    ."<td>$res</td></tr>\n";
        }
    }
?>
  <tr>
    <th colspan="2">
      <?php echo _b_('Corps'); ?>
    </th>
  </tr> 
  <tr>
    <td colspan="2">
      <pre><?php echo formatbody($post->body); ?></pre>
    </td>
  </tr>
  <tr>
    <th colspan="2">
      <?php echo _b_('Aperçu'); ?>
    </th>
  </tr> 
  <tr>
    <td class="<?php echo $css['nopadd']?>" colspan="2">
      <table class="<?php echo $css['overview']?>" cellpadding="0" 
      cellspacing="0" summary="overview">
<?php
$banana->spool->disp($ndx-$banana->tbefore,$ndx+$banana->tafter,$ndx);
?>
      </table>
    </td>
  </tr>
</table>
<?php
displayshortcuts();

require_once("include/footer.inc.php");
?>

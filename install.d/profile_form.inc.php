<?php
/********************************************************************************
* install.d/profile_form.inc.php : HTML form
* --------------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/
?>
<div class="title">
  <?php echo _('Bienvenue sur Banana !'); ?>
</div>

<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="POST">
  <table class="bicol" cellpadding="2" cellspacing="0" 
  summary="Profile">
    <tr class="pair">
      <th colspan="2">
        <?php echo _('Définis tes paramètres'); ?>
      </th>
    </tr>
    <tr class="impair">
      <td>
        <?php echo _('Nom (par exemple Jean Dupont)'; ?>
      </td>
      <td>
        <input type="text" name="profile_name" value="">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo _('Adresse mail'); ?>
      </td>
      <td>
        <input type="text" name="profile_mail" value="">
      </td>
    </tr>
    <tr class="impair">
      <td>
        <?php echo _('Organisation'); ?>
      </td>
      <td>
        <input type="text" name="profile_org" value="">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo _('Signature'); ?>
      </td>
      <td>
        <textarea name="profile_sig" rows="7" cols="50"></textarea>
      </td>
    </tr>
    <tr class="pair">
      <th colspan="2">
        <?php echo _('Affichage'); ?>
      </th>
    </tr>
    <tr class="impair">
      <td colspan="2">
        <input type="radio" name="displaytype" value="0" checked> 
          <?php echo _('Tous les messages'); ?>
      </td>
    </tr>
    <tr class="pair">
      <td colspan="2">
        <input type="radio" name="displaytype" value="1"> 
          <?php echo _('Seulement les fils de discussion comportant des messages non lus'); ?>
      </td>
    </tr>
    <tr class="pair">
      <th colspan="2">
        <?php echo _('Authentification sur le serveur NNTP'); ?>
      </th>
    </tr>
    <tr class="impair">
      <td>
        <?php echo _('Login (laisser anonyme pour un login en anonyme)'); ?>
      </td>
      <td>
        <input type="text" name="profile_login" value="anonymous">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo _('Mot de passe'); ?>
      </td>
      <td>
        <input type="password" name="profile_passwd" value="">
      </td>
    </tr>
    <tr class="impair">
      <td colspan="2" class="bouton">
        <input type="submit" name="action" value="OK">
      </td>
    </tr>
  </table>
</form>

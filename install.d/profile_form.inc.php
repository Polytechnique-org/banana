<?php
/********************************************************************************
* install.d/profile_form.inc.php : HTML form
* --------------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>

<div class="title">
  <?php echo $locale['profile']['title'];?>
</div>

<form action="<?php echo $_SERVER['REQUEST_URI']?>" method="POST">
  <table class="bicol" cellpadding="2" cellspacing="0" 
  summary="Profile">
    <tr class="pair">
      <th colspan="2">
        <?php echo $locale['profile']['define'];?>
      </th>
    </tr>
    <tr class="impair">
      <td>
        <?php echo $locale['profile']['name'];?>
      </td>
      <td>
        <input type="text" name="profile_name" value="">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo $locale['profile']['mail'];?>
      </td>
      <td>
        <input type="text" name="profile_mail" value="">
      </td>
    </tr>
    <tr class="impair">
      <td>
        <?php echo $locale['profile']['organization'];?>
      </td>
      <td>
        <input type="text" name="profile_org" value="">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo $locale['profile']['signature'];?>
      </td>
      <td>
        <textarea name="profile_sig"></textarea>
      </td>
    </tr>
    <tr class="pair">
      <th colspan="2">
        <?php echo $locale['profile']['display'];?>
      </th>
    </tr>
    <tr class="impair">
      <td colspan="2">
        <input type="radio" name="displaytype" value="0" checked> 
          <?php echo $locale['profile']['all'];?>
      </td>
    </tr>
    <tr class="pair">
      <td colspan="2">
        <input type="radio" name="displaytype" value="1"> 
          <?php echo $locale['profile']['new'];?>
      </td>
    </tr>
    <tr class="pair">
      <th colspan="2">
        <?php echo $locale['profile']['auth'];?>
      </th>
    </tr>
    <tr class="impair">
      <td>
        <?php echo $locale['profile']['login'];?>
      </td>
      <td>
        <input type="text" name="profile_login" value="anonymous">
      </td>
    </tr>
    <tr class="pair">
      <td>
        <?php echo $locale['profile']['passwd'];?>
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

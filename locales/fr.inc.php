<?php

function locale_date($_text) {
  $date = getdate(strtotime($_text));
  $days = array("Dimanche","Lundi","Mardi","Mercredi","Jeudi",
    "Vendredi","Samedi");
  $months = array("Janvier","F�vrier","Mars","Avril","Mai","Juin",
    "Juillet","Ao�t","Septembre","Octobre","Novembre","D�cembre");
  $rtext = $days[$date["wday"]]." ".$date["mday"]." "
    .$months[$date["mon"]-1]." ".$date["year"].", "
    .date("H:i",strtotime($_text))." (fuseau serveur)";
  return $rtext;
}

function locale_header_date($_text) {
  $date = getdate($_text);
  $now = time();
  $dnow = getdate($now);
  $days = array("dim","lun","mar","mer","jeu",
    "ven","sam");
  $months = array("janv.","f�v.","mars","avr.","mai","juin",
    "juil.","ao�t","sept.","oct.","nov.","d�c.");
  if (($now-$_text < 39600) || ($dnow["yday"]==$date["yday"])) {
    return date("H:i",$_text);
  } elseif (($now-$_text < 2*86400) and ((($date["yday"]-$dnow["yday"])%365)==1)) {
    return "hier ".date("H:i",$_text);
  } elseif ($now-$_text < 604800) {
    return $days[$date["wday"]]." ".date("H:i",$_text);
  } else {
    $day = $date["mday"];
    if ($date["mday"]==1) {
      $day.="<sup>er</sup>";
    }
    return "$day ".$months[$date["mon"]-1];
  }
}

if (!isset($locale['error'])) $locale['error'] =array();
if (!isset($locale['index'])) $locale['index'] =array();
if (!isset($locale['subscribe'])) $locale['subscribe'] =array();
if (!isset($locale['thread'])) $locale['thread'] =array();
if (!isset($locale['format'])) $locale['format'] =array();
if (!isset($locale['post'])) $locale['post'] =array();
if (!isset($locale['article'])) $locale['article'] =array();
if (!isset($locale['profile'])) $locale['profile'] =array();
if (!isset($locale['headers'])) $locale['headers'] =array();
if (!isset($locale['disconnect'])) $locale['disconnect'] =array();

$locale['error'] = array_merge(array(
  'title' => "Erreur !!!",
  'connect' => "Impossible de se connecter au serveur de forums",
  'credentials' => "L'authentification sur le serveur de forums a �chou�",
  'group' => "Impossible d'acc�der au forum",
  'post' => "Impossible d'acc�der au message. Le message a peut-�tre �t� annul�",
  'nogroup' => "Il n'y a pas de forum sur ce serveur"
),$locale['error']);

$locale['index'] = array_merge(array(
  'title' => "Les forums de Banana",
  'summary' => "Liste des forums",
  'total' => "Total",
  'unread' => "Nouveaux",
  'name' => "Nom",
  'description' => "Description",
  'newgroupstext' => "Les forums suivants ont �t� cr��s depuis ton dernier passage :"
),$locale['index']);

$locale['subscribe'] = array_merge(array(
  'title' => "Abonnements",
  'summary' => "Liste des forums",
  'total' => "Total",
  'subscribed' => "Abonn�",
  'name' => "Nom",
  'description' => "Description",
),$locale['subscribe']);

$locale['article'] = array_merge(array(
  'message' => "Message",
  'cancel' => "Voulez-vous vraiment annuler ce message ?",
  'okbtn' => "OK",
  'summary' => "Contenu du message",
  'headers' => "En-t�tes",
  'body' => "Corps",
  'overview' => "Aper�u"
),$locale['article']);

$locale['thread'] = array_merge(array(
  'group_a' => "",
  'group_b' => "Forum ",
  'date' => "Date",
  'subject' => "Sujet",
  'author' => "Auteur",
  'summary' => "Liste des messages"
),$locale['thread']);

$locale['post'] = array_merge($locale['post'],array(
  'badcancel' => "Impossible d'annuler le message",
  'canceled' => "Message annul�",
  'badpost' => "Impossible de poster le message",
  'posted' => "Message post�",
  'rghtcancel' => "Vous n'avez pas les permissions pour annuler ce message",
  'title' => "Nouveau message",
  'headers' => "En-t�tes",
  'name' => "Nom",
  'subject' => "Sujet",
  'newsgroups' => "Forums",
  'fu2' => "Suivi-�",
  'organization' => "Organisation",
  'body' => "Corps"
));

$locale['format'] = array_merge(array(
  'disconnection' => "D�connexion",
  'grouplist' => "Liste des forums",
  'group_a' => "",
  'group_b' => "",
  'followup' => "R�pondre",
  'newpost' => "Nouveau message",
  'cancel' => "Annuler ce message"
),$locale['format']);

$locale['profile'] = array_merge(array(
  'title' => "Bienvenue sur Banana !",
  'define' => "D�finis tes param�tres",
  'name' => "Nom (par exemple Jean Dupont)",
  'mail' => "Adresse mail",
  'organization' => "Organisation",
  'signature' => "Signature",
  'display' => "Affichage",
  'all' => "Tous les messages",
  'new' => "Seulement les fils de discussion comportant des messages non lus",
  'auth' => "Authentification sur le serveur NNTP",
  'login' => "Login (laisser anonyme pour un login en anonyme)",
  'passwd' => "Mot de passe"
),$locale['profile']);

$locale['disconnect'] = array_merge(array(
  'title' => "D�connexion effectu�e !",
  'back' => 'Retour au <a href="index.php">profil</a>'
),$locale['disconnect']);

$locale['headers'] = array_merge(array(
  'from' => 'De',
  'subject' => 'Sujet',
  'newsgroups' => 'Forums',
  'followup' => 'Suivi-A',
  'date' => 'Date',
  'organization' => 'Organisation',
  'references' => 'R�f�rences',
  'xface' => 'Image'
),$locale['headers']);

?>

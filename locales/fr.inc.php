<?php

function locale_date($text) {
  $date = getdate(strtotime($text));
  $days = array("Dimanche","Lundi","Mardi","Mercredi","Jeudi",
    "Vendredi","Samedi");
  $months = array("Janvier","F�vrier","Mars","Avril","Mai","Juin",
    "Juillet","Ao�t","Septembre","Octobre","Novembre","D�cembre");
  $rtext = $days[$date["wday"]]." ".$date["mday"]." "
    .$months[$date["mon"]-1]." ".$date["year"].", "
    .date("H:i",strtotime($text))." (heure serveur)";
  return $rtext;
}

$locale['error'] = array(
  'title' => "Erreur !!!",
  'connect' => "Impossible de se connecter au serveur de forums",
  'credentials' => "L'authentification sur le serveur de forums a �chou�",
  'group' => "Impossible d'acc�der au forum",
  'post' => "Impossible d'acc�der au message. Le message a peut-�tre �t� annul�",
  'nogroup' => "Il n'y a pas de forum sur ce serveur"
);

$locale['index'] = array(
  'title' => "Les forums de Banana",
  'summary' => "Liste des forums",
  'total' => "Total",
  'unread' => "Nouveaux",
  'name' => "Nom",
  'description' => "Description",
  'newgroupstext' => "Les forums suivants ont �t� cr��s depuis ton dernier passage :"
);

$locale['subscribe'] = array(
  'title' => "Abonnements",
  'summary' => "Liste des forums",
  'total' => "Total",
  'subscribed' => "Abonn�",
  'name' => "Nom",
  'description' => "Description",
);

$locale['article'] = array(
  'message' => "Message",
  'cancel' => "Voulez-vous vraiment annuler ce message ?",
  'okbtn' => "OK",
  'summary' => "Contenu du message",
  'headers' => "En-t�tes",
  'body' => "Corps",
  'overview' => "Aper�u"
);

$locale['thread'] = array(
  'group_a' => "",
  'group_b' => "Forum ",
  'date' => "Date",
  'subject' => "Sujet",
  'author' => "Auteur",
  'summary' => "Liste des messages"
);

$locale['post'] = array(
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
);

$locale['format'] = array(
  'datefmt' => 'd/m/Y',
  'disconnection' => "D�connexion",
  'grouplist' => "Liste des forums",
  'group_a' => "",
  'group_b' => "",
  'followup' => "R�pondre",
  'newpost' => "Nouveau message",
  'cancel' => "Annuler ce message"
);

$locale['profile'] = array(
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
);

$locale['disconnect'] = array(
  'title' => "D�connexion effectu�e !",
  'back' => 'Retour au <a href="index.php">profil</a>'
);

$locale['headers'] = array(
  'from' => 'De',
  'subject' => 'Sujet',
  'newsgroups' => 'Forums',
  'followup' => 'Suivi-A',
  'date' => 'Date',
  'organization' => 'Organisation',
  'references' => 'R�f�rences',
  'xface' => 'Image'
);

?>

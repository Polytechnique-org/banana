<?php
/********************************************************************************
* install.d/error.inc.php : central NNTP error messages
* -------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** outputs HTML error page
 * @param $_type STRING error type
 */
function error($_type) {
    global $css, $group;
    switch ($_type) {
        case "nntpsock":
            echo '<p class="error">'._('Impossible de se connecter au serveur de forums').'</p>';
            require_once("include/footer.inc.php");
            exit;

        case "nntpauth":
            echo '<p class="error">'._('L\'authentification sur le serveur de forums a échoué').'</p>';
            require_once("include/footer.inc.php");
            exit;

        case "nntpgroups":
            echo "<p class=\"{$css['normal']}\">";
            echo _('Il n\'y a pas de forum sur ce serveur').'</p>';
            require_once("include/footer.inc.php");
            exit;

        case "nntpspool":
            echo "<div class=\"{$css['bananashortcuts']}\">\n";
            echo "[<a href=\"index.php\">Liste des forums</a>]\n";
            echo "</div>\n";
            echo '<p class="error">'._('Impossible d\'accéder au forum').'</p>';
            require_once("footer.inc.php");
            exit;

        case "nntpart":
            echo "<div class=\"{$css['bananashortcuts']}\">\n";
            echo "[<a href=\"index.php\">Liste des forums</a>] \n";
            echo "[<a href=\"thread.php?group=$group\">$group</a>] \n";
            echo "</div>\n";
            echo '<p class="error">'._('Impossible d\'accéder au message.  Le message a peut-être été annulé').'</p>';
            require_once("footer.inc.php");
            exit;
    }
}

?>

<?php
/********************************************************************************
* install.d/format.inc.php : HTML output subroutines
* --------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/


/** contextual links 
 * @return STRING HTML output
 */
function displayshortcuts() {
    global $banana,$first,$group,$post,$id;
    $sname = basename($_SERVER['SCRIPT_NAME']);

    echo '<div class="shortcuts">';
    echo '[<a href="disconnect.php">'._b_('Déconnexion').'</a>] ';

    switch ($sname) {
        case 'thread.php' :
            echo '[<a href="index.php">'._b_('Liste des forums').'</a>] ';
            echo "[<a href=\"post.php?group=$group\">"._b_('Nouveau message')."</a>] ";
            if (sizeof($banana->spool->overview)>$banana->tmax) {
                for ($ndx=1; $ndx<=sizeof($banana->spool->overview); $ndx += $banana->tmax) {
                    if ($first==$ndx) {
                        echo "[$ndx-".min($ndx+$banana->tmax-1,sizeof($banana->spool->overview))."] ";
                    } else {
                        echo "[<a href=\"".$_SERVER['PHP_SELF']."?group=$group&amp;first="
                            ."$ndx\">$ndx-".min($ndx+$banana->tmax-1,sizeof($banana->spool->overview))
                            ."</a>] ";
                    }
                }
            }
            break;
        case 'article.php' :
            echo '[<a href="index.php">'._b_('Liste des forums').'</a>] ';
            echo "[<a href=\"thread.php?group=$group\">$group</a>] ";
            echo "[<a href=\"post.php?group=$group&amp;id=$id&amp;type=followup\">"._b_('Répondre')."</a>] ";
            if (checkcancel($post->headers)) {
                echo "[<a href=\"article.php?group=$group&amp;id=$id&amp;type=cancel\">"._b_('Annuler ce message')."</a>] ";
            }
            break;
        case 'post.php' :
            echo '[<a href="index.php">'._b_('Liste des forums').'</a>] ';
            echo "[<a href=\"thread.php?group=$group\">$group</a>]";
            break;
    }
    echo '</div>';
}

?>


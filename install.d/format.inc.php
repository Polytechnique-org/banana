<?php
/********************************************************************************
* install.d/format.inc.php : HTML output subroutines
* --------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** produces HTML ouput for header section in post.php
 * @param $_header STRING name of the header
 * @param $_text STRING value of the header
 * @param $_spool OBJECT spool object for building references
 * @return STRING HTML output
 */

function formatDisplayHeader($_header,$_text,$_spool) {
    switch ($_header) {
        case "date": 
            return formatDate($_text);
        
        case "followup":
            case "newsgroups":
            $res = "";
            $groups = preg_split("/(\t| )*,(\t| )*/",$_text);
            foreach ($groups as $g) {
                $res.='<a href="thread.php?group='.$g.'">'.$g.'</a>, ';
            }
            return substr($res,0, -2);

        case "from":
            return formatFrom($_text);
        
        case "references":
            $rsl = "";
            $ndx = 1;
            $text=str_replace("><","> <",$_text);
            $text=preg_split("/( |\t)/",strtr($text,$_spool->ids));
            $parents=preg_grep("/^\d+$/",$text);
            $p=array_pop($parents);
            while ($p) {
                $rsl .= "<a href=\"article.php?group={$_spool->group}"
                    ."&amp;id=$p\">$ndx</a> ";
                $_spool->overview[$p]->desc++;
                $p = $_spool->overview[$p]->parent;
                $ndx++;
            }
            return $rsl;

        case "xface":
            return '<img src="xface.php?face='.base64_encode($_text)
            .'"  alt="x-face" />';
        
        default:
            return htmlentities($_text);
    }
}

/** contextual links 
 * @return STRING HTML output
 */
function displayshortcuts() {
    global $news,$first,$spool,$group,$post,$id;
    $sname = basename($_SERVER['SCRIPT_NAME']);

    echo '<div class="shortcuts">';
    echo '[<a href="disconnect.php">'._b_('Déconnexion').'</a>] ';

    switch ($sname) {
        case 'thread.php' :
            echo '[<a href="index.php">'._b_('Liste des forums').'</a>] ';
            echo "[<a href=\"post.php?group=$group\">"._b_('Nouveau message')."</a>] ";
            if (sizeof($spool->overview)>$news['max']) {
                for ($ndx=1; $ndx<=sizeof($spool->overview); $ndx += $news['max']) {
                    if ($first==$ndx) {
                        echo "[$ndx-".min($ndx+$news['max']-1,sizeof($spool->overview))."] ";
                    } else {
                        echo "[<a href=\"".$_SERVER['PHP_SELF']."?group=$group&amp;first="
                            ."$ndx\">$ndx-".min($ndx+$news['max']-1,sizeof($spool->overview))
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


<?php
/********************************************************************************
* include/groups.inc.php : class for group lists
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** class for group lists
 */

define ( 'BANANA_GROUP_ALL', 0 );
define ( 'BANANA_GROUP_SUB', 1 );
define ( 'BANANA_GROUP_NEW', 2 );
 
class BananaGroups {
    /** group list */
    var $overview = Array();
    /** last update */
    var $date;

    var $type;

    /** constructor
     */

    function BananaGroups($_type = BANANA_GROUP_SUB) {
        global $banana;

        $this->type = $_type;
        $desc       = $banana->nntp->xgtitle();

        $this->load();
        
        if (empty($this->overview) && $_type == BANANA_GROUP_SUB) {
            $this->type = BANANA_GROUP_ALL;
            $this->load();
        }
    }

    /** Load overviews
     */
    function load()
    {
        global $banana;

        if ($this->type == BANANA_GROUP_NEW) {
            $list = $banana->nntp->newgroups($banana->profile['lastnews']);
        } else {
            $list = $banana->nntp->liste();
            if ($this->type == BANANA_GROUP_SUB) {
                $mylist = Array();
                foreach ($banana->profile['subscribe'] as $g) {
                    if (isset($list[$g])) {
                        $mylist[$g] = $list[$g];
                    }
                }
                $list = $mylist;
            }
        }

        foreach ($list as $g=>$l) {
            $this->overview[$g][0] = isset($desc[$g]) ? $desc[$g] : '-';
            $this->overview[$g][1] = $l[0];
        }
        ksort($this->overview);
    }

    /** updates overview 
     * @param date INTEGER date of last update
     */
    function update($_date) {
        global $banana;
        $serverdate = $banana->nntp->date();
        if (!$serverdate) $serverdate=time();
        $newlist = $banana->nntp->newgroups($_date);
        if (!$newlist) return false;
        $this->date = $serverdate;
        foreach (array_keys($newlist) as $g) {
            $groupstat = $banana->nntp->group($g);
            $groupdesc = $banana->nntp->xgtitle($g);
            $this->overview[$g][0]=($groupdesc?$groupdesc:"-");
            $this->overview[$g][1]=$groupstat[0];
        }
        return true;
    }

    function to_html($show_form = false)
    {
        global $banana;
        if (empty($this->overview)) {
            return;
        }

        $html  = '<table class="bicol banana_group" cellspacing="0" cellpadding="2">'."\n";
        $html .= '<tr><th>'._b_('Total').'</th><th>';
        if ($show_form) {
            $html .= _b_('Abo.').'</th><th>';
        } elseif ($this->type == BANANA_GROUP_SUB) {
            $html .= _b_('Nouveaux').'</th><th>';
        }
        $html .= _b_('Nom').'</th><th>'._b_('Description').'</th></tr>'."\n";

        $b = true;
        foreach ($this->overview as $g => $d) {
            $b     = !$b;
            $ginfo = $banana->nntp->group($g);
            $new   = count($banana->nntp->newnews($banana->profile['lastnews'],$g));

            $html .= '<tr class="'.($b ? 'pair' : 'impair').'">'."\n";
            $html .= "<td class='all'>{$ginfo[0]}</td>";
            if ($show_form) {
                $html .= '<td class="new"><input type="checkbox" name="subscribe[]" value="'.$g.'"';
                if (in_array($g, $banana->profile['subscribe'])) {
                    $html .= ' checked="checked"';
                }
                $html .= ' /></td>';
            } elseif ($this->type == BANANA_GROUP_SUB) {
                $html .= '<td class="new">'.($new ? $new : '-').'</td>';
            }
            $html .= '<td class="grp">' . makeHREF(Array('group' => $g), $g) . '</td><td class="dsc">' . $d[0] . '</td></tr>';
        }

        $html .= '</table>';

        if ($show_form) {
            return '<form method="post" action="' . htmlentities(makeLink(Array())) . '">'
				. '<div class="center"><input type="submit" value="Valider" name="validsubs" /></div>'
                . $html . '<div class="center"><input type="submit" value="Valider" name="validsubs" /></div></form>';
        }
        
        return $html;
    }
}

?>

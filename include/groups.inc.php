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

class BananaGroups {
    /** group list */
    var $overview;
    /** last update */
    var $date;

    /** constructor
     */

    function BananaGroups($_type=0) {
        global $banana;
        $desc = $banana->nntp->xgtitle();
        if ($_type==1) {
            $list = $banana->nntp->newgroups($banana->profile['lastnews']);
        } else {
            $list = $banana->nntp->liste();
            if ($_type == 0) {
                $Mylist = Array();
                foreach ($banana->profile['subscribe'] as $g) {
                    if (isset($list[$g])) {
                        $mylist[$g] = $list[$g];
                    }
                }
                $list = $mylist;
            }
        }
        if (empty($list)) {
            $this->overview=array();
            return false;
        }

        foreach ($list as $g=>$l) {
            $this->overview[$g][0] = isset($desc[$g]) ? $desc[$g] : '-';
            $this->overview[$g][1] = $l[0];
        }
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
}

?>

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

class groups {
  /** group list */
  var $overview;
  /** last update */
  var $date;
  
  /** constructor
   * @param $_nntp RESOURCE handle to NNTP socket
   */

  function groups(&$_nntp,$_type=0) {
    global $profile;
    $desc=$_nntp->xgtitle();
    if ($_type==1) {
      $list=$_nntp->newgroups($profile['lastnews']);
    } else {
      $list=$_nntp->liste();
    }
    if (!$list) {
      $this->overview=array();
      return false;
    }
    if (isset($desc)) {
      foreach ($desc as $g=>$d) {
        if ((($_type==0) and (in_array($g,$profile['subscribe']) or !count($profile['subscribe'])))
          or (($_type==1) and in_array($g,array_keys($list)))
          or ($_type==2)) {
          $this->overview[$g][0]=$d;
          $this->overview[$g][1]=$list[$g][0];
        }
      }
      foreach (array_diff(array_keys($list),array_keys($desc)) as $g) {
        if ((($_type==0) and (in_array($g,$profile['subscribe']) or !count($profile['subscribe'])))
          or (($_type==1) and in_array($g,array_keys($list)))
          or ($_type==2)) {
          $this->overview[$g][0]="-";
          $this->overview[$g][1]=$list[$g][0];
        }
      }
    } else {
      foreach ($list as $g=>$l) {
        if ((($_type==0) and (in_array($g,$profile['subscribe']) or !count($profile['subscribe'])))
          or (($_type==1) and in_array($g,array_keys($list)))
          or ($_type==2)) {
          $this->overview[$g][0]="-";
          $this->overview[$g][1]=$l[0];
        }
      }
    }
    return true;
  }

  /** updates overview 
   * @param $_nntp RESOURCE handle to NNTP socket
   * @param date INTEGER date of last update
   */
  function update(&$_nntp,$_date) {
    $serverdate = $_nntp->date();
    if (!$serverdate) $serverdate=time();
    $newlist = $_nntp->newgroups($_date);
    if (!$newlist) return false;
    $this->date = $serverdate;
    foreach (array_keys($newlist) as $g) {
      $groupstat = $_nntp->group($g);
      $groupdesc = $_nntp->xgtitle($g);
      $this->overview[$g][0]=($groupdesc?$groupdesc:"-");
      $this->overview[$g][1]=$groupstat[0];
    }
    return true;
  }
}

?>

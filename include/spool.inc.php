<?php
/********************************************************************************
* include/spool.inc.php : spool subroutines
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** Class spoolhead
 *  class used in thread overviews
 */
class spoolhead {
  /** date (timestamp) */
  var $date;
  /** subject */
  var $subject;
  /** author */
  var $from;
  /** reference of parent */
  var $parent;
  /** array of children */
  var $children;
  /** true if post is read */
  var $isread;
  /** number of posts deeper in this branch of tree */
  var $desc;
  /**  same as desc, but counts only unread posts */
  var $descunread;

  /** constructor
   * @param $_date INTEGER timestamp of post
   * @param $_subject STRING subject of post
   * @param $_from STRING author of post
   * @param $_desc INTEGER desc value (1 for a new post)
   * @param $_read BOOLEAN true if read
   * @param $_descunread INTEGER descunread value (0 for a new post)
   */

  function spoolhead($_date,$_subject,$_from,$_desc=1,$_read=true,$_descunread=0) {
    $this->date=$_date;
    $this->subject=$_subject;
    $this->from=$_from;
    $this->children=array();
    $this->desc=$_desc;
    $this->isread=$_read;
    $this->descunread=$_descunread;
  }
}

/** Class spool
 * builds and updates spool 
 */

class spool {
#  var $date;
  /**  spool */
  var $overview;
  /**  group name */
  var $group;
  /**  array msgid => msgnum */
  var $ids;

  /** constructor
   * @param $_nntp RESOURCE NNTP socket filehandle
   * @param $_group STRING group name
   * @param $_display INTEGER 1 => all posts, 2 => only threads with new posts
   * @param $_since INTEGER time stamp (used for read/unread)
   */
   
  function spool(&$_nntp,$_group,$_display=0,$_since=""){
	global $news;
    $prefix_path=(preg_match("/\/scripts\/?$/",getcwd())?"..":".");    
    $groupinfo = $_nntp->group($_group);
    if (!$groupinfo) {
      $this = false;
      return false;
    }
	$spoolfile=realpath("$prefix_path/spool/spool-$_group.dat");
    if (file_exists($spoolfile)) {
      $f = fopen($spoolfile,"r");
      $this = unserialize(fread($f,filesize($spoolfile)));
      fclose($f);
      $keys = array_values($this->ids);
      rsort($keys);
      $first = max($groupinfo[2]-$news['maxspool'],$groupinfo[1]);
      $last = $groupinfo[2];
	  // remove expired messages
	  $msgids=array_flip($this->ids);
	  for ($id=min(array_keys($this->overview)); $id<$first; $id++) { 
		$this->delid($id);
	  }
	  $this->ids=array_flip($msgids);
	  $first=max(array_keys($this->overview))+1;
    } else {
      $first = max($groupinfo[2]-$news['maxspool'],$groupinfo[1]);
      $last = $groupinfo[2];
      $this->group = $_group;
    }
	
    if (($first<=$groupinfo[2]) && ($groupinfo[0]!=0)) {
      $dates = array_map("strtotime",
        $_nntp->xhdr("Date","$first-$last"));
      $msgids=$_nntp->xhdr("Message-ID","$first-$last");
      $subjects = array_map("headerdecode",$_nntp->xhdr("Subject",
        "$first-$last"));
      $froms = array_map("headerdecode",$_nntp->xhdr("From",
        "$first-$last"));
      $refs = $_nntp->xhdr("References","$first-$last");
#      $this->date=$nntp->date;
      if (isset($this->ids)) {
        $this->ids=array_merge($this->ids,array_flip($msgids));
      } else {
        $this->ids=array_flip($msgids);
      }

      foreach ($msgids as $id=>$msgid) {
        if (isset($this->overview[$id])) {
          $msg = $this->overview[$id];
          $msg->desc++;
        } else {
          $msg = new spoolhead($dates[$id],$subjects[$id],$froms[$id],1);
        }
        $refs[$id]=str_replace("><","> <",$refs[$id]);
        $msgrefs=preg_split("/( |\t)/",strtr($refs[$id],$this->ids));
        $parents=preg_grep("/^\d+$/",$msgrefs);
        $msg->parent=array_pop($parents);
        $p = $msg->parent;
        while ($p) {
          if (isset($this->overview[$p])) {
            $this->overview[$p]->desc++;
            if (isset($this->overview[$p]->parent)) {
              $p = $this->overview[$p]->parent;
            } else {
              $p = false;
            }
          } else {
            $this->overview[$p] = new spoolhead($dates[$p],$subjects[$p],$froms[$p],1);
            $p = false; 
          }
        }
        if ($msg->parent!="") 
          $this->overview[$msg->parent]->children[]=$id;
        $this->overview[$id] = $msg;
      }
      uasort($this->overview,"spoolcompare");
      $f = fopen("$prefix_path/spool/spool-$_group.dat","w");
      fputs($f,serialize($this));
      fclose($f);
    }
    
    if ($_since != "") {
      $newpostsids = $_nntp->newnews($_since,$_group);
      if (sizeof($newpostsids)) {
        $newpostsids = array_intersect($newpostsids,
          array_keys($this->ids));
        if ($newpostsids && !is_null($newpostsids)) {
          foreach ($newpostsids as $mid) {
            $this->overview[$this->ids[$mid]]->isread=false;
            $this->overview[$this->ids[$mid]]->descunread=1;
            $parentmid = $this->overview[$this->ids[$mid]]->parent;
            while (!is_null($parentmid)) {
              $this->overview[$parentmid]->descunread++;
              $parentmid = $this->overview[$parentmid]->parent;
            }
          }
        }
      }
      if (sizeof($newpostsids)>0) {
        $flipids = array_flip($this->ids);
        switch ($_display) {
          case 1:
            foreach ($this->overview as $i=>$p) {
              if (isset($this->overview[$i]) &&
              !isset($this->overview[$i]->parent) && 
              ($this->overview[$i]->descunread==0)) {
                $this->killdesc($i);
              }
            }
            break;
          case 2:
            foreach ($this->overview as $i=>$p) {
              if ($p->isread) {
                unset($this->overview[$i]);
                unset($flipids[$i]);
              }
            }
            $this->ids=array_flip($flipids);
            break;
        }
      }
    }
    return true;
  }

  /** kill post and childrens
   * @param $_id MSGNUM of post
   */

  function killdesc($_id) {
    if (sizeof($this->overview[$_id]->children)) {
      foreach ($this->overview[$_id]->children as $c) {
        $this->killdesc($c);
      }
    }
    unset($this->overview[$_id]);
#    $flipid=array_flip($this->ids);
#    unset($flipid[$id]);
#    $this->ids=array_flip($flipid);
  }

  /** delete a post from overview
   * @param $_id MSGNUM of post
   */

  function delid($_id) {
    if (isset($this->overview[$_id])) {
      if (sizeof($this->overview[$_id]->parent)) {
        $this->overview[$this->overview[$_id]->parent]->children = 
        array_diff($this->overview[$this->overview[$_id]->parent]->children,
        array($_id));
        if (sizeof($this->overview[$_id]->children)) {
          $this->overview[$this->overview[$_id]->parent]->children = 
          array_merge($this->overview[$this->overview[$_id]->parent]->children,
          $this->overview[$_id]->children);
          foreach ($this->overview[$_id]->children as $c) {
            $this->overview[$c]->parent = $this->overview[$_id]->parent;
          }
        }
        $p = $this->overview[$_id]->parent;
        while ($p) {
          $this->overview[$p]->desc--;
          $p = $this->overview[$p]->parent;
        }
      } elseif (sizeof($this->overview[$_id]->children)) {
        foreach ($this->overview[$_id]->children as $c) {
          $this->overview[$c]->parent = null;
        }
      }
      unset($this->overview[$_id]);
      $ids = array_flip($this->ids);
      unset($ids[$_id]);
      $this->ids = array_flip($ids);
      $f = fopen("$prefix_path/spool/spool-{$this->group}.dat","w");
      fputs($f,serialize($this));
      fclose($f);
    }
  }

  /** displays children tree of a post
   * @param $_id INTEGER MSGNUM of post
   * @param $_index INTEGER linear number of post in the tree
   * @param $_first INTEGER linear number of first post displayed
   * @param $_last INTEGER linear number of last post displayed
   * @param $_ref STRING MSGNUM of current post 
   * @param $_pfx_node STRING prefix used for current node
   * @param $_pfx_end STRING prefix used for children of current node
   * @param $_head BOOLEAN true if first post in thread
   */

  function disp_desc($_id,$_index="",$_first=0,$_last=0,$_ref="",
  $_pfx_node="", $_pfx_end="",$_head=true) {
    global $css;
    $debug = false;
    $spfx_f = '<img src="img/k1.gif" height="21" width="9" alt="o" />'; 
    $spfx_n = '<img src="img/k2.gif" height="21" width="9" alt="*" />'; 
    $spfx_T = '<img src="img/T.gif" height="21" width="12" alt="+" />';
    $spfx_L = '<img src="img/L.gif" height="21" width="12" alt="`" />';
    $spfx_s = '<img src="img/s.gif" height="21" width="5" alt="-" />';
    $spfx_e = '<img src="img/e.gif" height="21" width="12" alt="&nbsp;" />';
    $spfx_I = '<img src="img/I.gif" height="21" width="12"alt="|" />';
    
    if ($_index == "") $_index = $this->getndx($_id);
    
    if (!sizeof($this->overview[$_id]->children) && ($_index<=$_last)
    && ($_index>=$_first)) {
      echo '<tr class="'.($_index%2?$css["pair"]:$css["impair"])."\">\n";
      echo "<td class=\"{$css['date']}\">"
        .formatSpoolHeader("date",$this->overview[$_id]->date,$_id,
        $this->group,($_index==$_ref),$this->overview[$_id]->isread)
        ." </td>\n";
      echo "<td class=\"{$css['subject']}\"><div class=\"{$css['tree']}\">"
        .$_pfx_node.($_head?$spfx_f:$spfx_s)."</div>"
        .formatSpoolHeader("subject",$this->overview[$_id]->subject,$_id,
        $this->group,($_index==$_ref),$this->overview[$_id]->isread)
        .($debug?" $_id $_index ".
        $this->overview[$_id]->desc." ".$this->overview[$_id]->descunread." ":"")." </td>\n";
      echo "<td class=\"{$css['author']}\">"
        .formatSpoolHeader("from",$this->overview[$_id]->from,$_id,
        $this->group,($_index==$_ref),$this->overview[$_id]->isread)
        ." </td>\n</tr>";
      return true;
    } 
    $children = $this->overview[$_id]->children;
    if (($_index<=$_last) && ($_index>=$_first)) {
      echo '<tr class="'.($_index%2?$css["pair"]:$css["impair"])."\">\n";
      echo "<td class=\"{$css['date']}\">"
         .formatSpoolHeader("date",$this->overview[$_id]->date,$_id,
         $this->group,($_index==$_ref),$this->overview[$_id]->isread)
         ." </td>\n";
      echo "<td class=\"{$css['subject']}\"><div class=\"{$css['tree']}\">"
        .$_pfx_node.$spfx_n."</div>"
        .formatSpoolHeader("subject",$this->overview[$_id]->subject,$_id,
        $this->group,($_index==$_ref),$this->overview[$_id]->isread)
        .($debug?" $_id $_index ".
        $this->overview[$_id]->desc." ".$this->overview[$_id]->descunread." ":"")." </td>\n";
      echo "<td class=\"{$css['author']}\">"
        .formatSpoolHeader("from",$this->overview[$_id]->from,$_id,
        $this->group,($_index==$_ref),$this->overview[$_id]->isread)
        ." </td>\n</tr>";
    }
	$index=$_index+1;
    while ($child = array_shift($children)) {
      if (($index+$this->overview[$child]->desc-1>=$_first)
      ||($index<$_last)){
        if (sizeof($children)) {
          $this->disp_desc($child,$index,$_first,$_last,$_ref,
            $_pfx_end.$spfx_T,$_pfx_end.$spfx_I,false);
        } else {
          $this->disp_desc($child,$index,$_first,$_last,$_ref,
            $_pfx_end.$spfx_L,$_pfx_end.$spfx_e,false);
        }
      }
      $index += $this->overview[$child]->desc;
    }
  }

  /** Displays overview
   * @param $_first INTEGER MSGNUM of first post
   * @param $_last INTEGER MSGNUM of last post
   * @param $_ref STRING MSGNUM of current/selectionned post
   */

  function disp($_first=0,$_last=0,$_ref="") {
    global $css;
    $index=1;
    if (sizeof($this->overview)) {
      foreach ($this->overview as $id=>$msg) {
        if (!sizeof($msg->parent)) {
          $this->disp_desc($id,$index,$_first,$_last,$_ref);
          $index += $msg->desc;
        }
      }
    } else {
     echo "<tr class=\"{$css['pair']}\">\n";
     echo "\t<td colspan=\"3\">\n";
     echo "\t\tNo post in this newsgroup\n";
     echo "\t</td>\n";
     echo "</tr>\n";
    }
  }
  
  /** computes linear post index
   * @param $_id INTEGER MSGNUM of post
   * @return INTEGER linear index of post
   */

  function getndx($_id) {
    $ndx = 1;
    // on remonte l'arbre
    $id_parent = $this->overview[$_id]->parent;
    $id_curr = $_id;
    while (!is_null($id_parent)) {
      for ($i=0; $i<array_search($id_curr,
      $this->overview[$id_parent]->children) ; $i++) {
        $ndx += $this->overview[$this->overview[$id_parent]->children[$i]]->desc;
      }
      $ndx++; //noeud père
      $id_curr = $id_parent;
      $id_parent = $this->overview[$id_curr]->parent;
    }
    // on compte les threads précédents
    foreach ($this->overview as $i=>$p) {
      if ($i==$id_curr) {
        break;
      }
      if (is_null($p->parent)) {
        $ndx += $this->overview[$i]->desc;
      }
    }
    return $ndx;
  }
}

?>

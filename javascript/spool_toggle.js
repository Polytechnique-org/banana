/********************************************************************************
* javascript/spool_toggle : script for folding/unfolding threads in spool
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** prevent IE from launching two opposite toggle actions at the same time
 * Usual parameters are:
 *  - 0 : no action is running
 *  - 1 : folding
 *  - 2 : unfolding  
 */
var banana_toggle = 0;

/** Release banana_toggle a little time after action is done
 *  called with : setTimeout(banana_release_toggle, 10);
 */
function banna_release_toggle() {
  banana_toggle = 0;
}

/** Unfold a thread by showing all its sons
 * called on the img element of the thread
 */ 
function banana_unfold_thread() {
  // don't unfold if already folding somewhere
  if (banana_toggle == 1) {
    return;
  }
  banana_toggle = 2;
  var myid = $(this).parent().parent().parent().attr("id").replace(/[0-9]+_/,"");
  // show all sons
  $("tr[@id^="+myid+"_]").each(banana_subunfold_thread);
  // change tree icon and prepare for folding
  $(this).
    attr("src",this.src.replace(/k3/,"k2")).
    attr("alt","*").
    unbind("click",banana_unfold_thread).
    click(banana_fold_thread);
  setTimeout(banna_release_toggle, 10);
}

/** Fold a thread by hiding all its sons
 * called on the img element of the thread
 */ 
function banana_fold_thread() {
  // don't fold if already unfolding somewhere
  if (banana_toggle == 2) {
    return;
  }
  banana_toggle = 1;
  var myid = $(this).parent().parent().parent().attr("id").replace(/[0-9]+_/,"");
  // hide all sons
  $("tr[@id^="+myid+"_]").each(banana_subfold_thread);
  // change tree icon and prepare for unfolding
  $(this).
    attr("src",this.src.replace(/k2/,"k3")).
    attr("alt","+").
    unbind("click",banana_fold_thread).
    click(banana_unfold_thread);
  setTimeout(banna_release_toggle, 10);
}

/** Show a son of a thread when unfolding
 * called on the tr element of the son
 */ 
function banana_subunfold_thread() {
  // show the element before working on sons
  // otherwise they could be hidden and not managed
  $(this).show();
  // if this son has subsons and is unfold
  if ($("img[@alt='*']", this).size()) {
    // show subsons
    var myid = $(this).attr("id").replace(/[0-9]+_/,"");
    $("tr[@id^="+myid+"_]").each(banana_subunfold_thread);
  }
}

/** Hide a son of a thread when folding
 * called on the tr element of the son
 */ 
function banana_subfold_thread() {
  // if this son has subsons and is unfold
  if ($("img[@alt='*']", this).size()) {
    // hide subsons
    var myid = $(this).attr("id").replace(/[0-9]+_/,"");
    $("tr[@id^="+myid+"_]").each(banana_subfold_thread);
  }
  // hide element only after working on sons
  // otherwise they could be hidden and not managed  
  $(this).hide();
}

/** Fold all threads in page
 */
function banana_fold_all() {
  $("tr img[alt='*'][@src*=k2]").each(banana_fold_thread);
}

/** Unfold all threads in page
 */
function banana_unfold_all() {
  $("tr img[alt='+'][@src*=k3]").each(banana_unfold_thread);
}

// prepare for folding
$(document).ready( function() {
 $("tr img[@alt='*'][@src*='k2']").
  css("cursor","pointer").
  click(banana_fold_thread);
});

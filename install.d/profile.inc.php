<?php
/********************************************************************************
* install.d/profile.inc.php : class for posts
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** checkcancel : sets cancel rights
 * @param $_headers OBJECT headers of message to cancel
 * @return BOOLEAN true if user has right to cancel message
 */
function checkcancel($_headers) {
    return ($_headers->from == $_SESSION['name']." <".$_SESSION['mail'].">");
}

/** getprofile : sets profile variables
 * @return ARRAY associative array. Keys are 'name' (name), 'sig' (signature), 'org' 
 *   (organization), 'display' (display threads with new posts only or all threads),
 *   'lastnews' (timestamp for empasizing new posts)
 */

function getprofile() {
    $array['name']      = $_SESSION['name']." <".htmlentities($_SESSION['mail']).">";
    $array['sig']       = $_SESSION['sig'];
    $array['org']       = $_SESSION['org'];
    $array['customhdr'] = "";
    $array['display']   = $_SESSION['displaytype'];
    $array['lastnews']  = time()-86400;
    $array['locale']    = 'fr';
    $array['subscribe'] = array();
    $array['dropsig']   = true;

    setlocale(LC_MESSAGE, $array['locale']);
    setlocale(LC_TIME, $array['locale']);
    return $array;
}
?>

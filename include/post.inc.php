<?php
/********************************************************************************
* include/posts.inc.php : class for posts
* -----------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

/** class for posts
 */

class Post {
  /** headers */
  var $headers;
  /** body */
  var $body;

  /** constructor
   * @param $_nntp RESOURCE handle to NNTP socket
   * @param $_id STRING MSGNUM or MSGID (a group should be selected in this case)  
   */
  function post(&$_nntp,$_id) {
    $this->headers = new headers($_nntp,$_id);
    if (!$this->headers) {
      $this = false;
      return false;
    }
    $this->body = join("\n",$_nntp->body($_id));
      if ((isset($this->headers->contentencoding)) && 
      (preg_match("/base64/",$this->headers->contentencoding))) {
        $this->body = base64_decode($this->body);
      }
      if ((isset($this->headers->contentencoding)) && 
      (preg_match("/quoted-printable/",$this->headers->contentencoding))) {
        $this->body = quoted_printable_decode($this->body);
      }
    if (!$this->body) {
      $this = false;
      return false;
    }
  }
}

/** class for headers
 */

class Headers {
  /** MSGNUM : *local* spool id */
  var $nb;            // numéro du post
  /** MSGID : Message-ID */
  var $msgid;         // Message-ID
  /** From header */
  var $from;          // From
  /** Name (if present in From header) */
  var $name;
  /** Mail (in From header) */
  var $mail;
  /** Subject header */
  var $subject;       // Subject
  /** Newsgroup¨ header */
  var $newsgroups;    // Newsgroups
  /** Followup-To header */
  var $followup;
  /** Content-Type header */
  var $contenttype;
  /** Content-Transfer-Encoding header */
  var $contentencoding;
  /** Date header */
  var $date;
  /** Organization header */
  var $organization;
  /** References header */
  var $references;

  /** constructor
   * @param $_nntp RESOURCE handle to NNTP socket
   * @param $_id STRING MSGNUM or MSGID
   */
   
  function headers(&$_nntp,$_id) {
    global $news;
    $hdrs = $_nntp->head($_id);
    if (!$hdrs) {
      $this = false;
      return false;
    }
    // parse headers
    foreach ($hdrs as $line) {
      if (preg_match("/^[\t\r ]+/",$line)) {
        $line = ($lasthdr=="X-Face"?"":" ").ltrim($line);
        if (in_array($lasthdr,array_keys($news['head']))) 
          $this->{$news['head'][$lasthdr]} .= $line;
      } else {
        list($hdr,$val) = split(":[ \t\r]*",$line,2);
        if (in_array($hdr,array_keys($news['head']))) 
          $this->{$news['head'][$hdr]} = $val;
        $lasthdr = $hdr;
      }
    }
    // decode headers
    foreach ($news['hdecode'] as $hdr) {
      if (isset($this->$hdr)) {
        $this->$hdr = headerDecode($this->$hdr);
      }
    }
    // sets name and mail
    $this->name = $this->from;
    $this->mail = $this->from;
    if (preg_match("/(.*)<(.*)@(.*)>/",$val,$match)) {
      $this->name = str_replace("\"","",trim($match[1]));
      $this->mail = $match[2]."@".$match[3];
    }
    if (preg_match("/([\w\.]+)@([\w\.]+) \((.*)\)/",$val,$match)) {
      $this->name = trim($match[3]);
      $this->mail = $match[1]."@".$match[2];
    }
    $this->nb=$id;
    $retour->numerr=0;
  }
}

?>

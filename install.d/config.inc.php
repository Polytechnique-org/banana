<?php

/** comparison function for the overview 
 * @param $a OBJECT spoolhead 
 * @param $b OBJECT spoolhead
 * @return
 */

function spoolcompare($a,$b) {
  global $news;
  return ($b->date>=$a->date);
}

// spool config in spool.inc.php
$news['maxspool'] = 3000;

// encoded headers
$news['hdecode'] = array('from','name','organization','subject');

// headers in article.php
$news['head'] = array(
  'From' => 'from',
  'Subject' => 'subject',
  'Newsgroups' => 'newsgroups',
  'Followup-To' => 'followup',
  'Date' => 'date',
  'Organization' => 'organization',
  'References' => 'references',
  'X-Face' => 'xface',
  );

// overview configuration in article.php
$news['threadtop'] = 5;
$news['threadbottom'] = 5;

// wordwrap configuration
$news['wrap'] = 80;

// overview configuration in thread.php
$news['max'] = 50;

// custom headers in post.php
$news['customhdr'] = "Content-Type: text/plain; charset=iso-8859-15\n"
  ."Mime-Version: 1.0\n"
  ."Content-Transfer-Encoding: 8bit\n"
  ."HTTP-Posting-Host: ".gethostbyname($_SERVER['REMOTE_ADDR'])."\n"
  ."User-Agent: Banana 0.7beta\n";

?>

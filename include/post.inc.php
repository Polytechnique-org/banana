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

class BananaPost
{
    var $nb;
    /** headers */
    var $headers;
    /** body */
    var $body;
    /** poster name */
    var $name;

    /** constructor
     * @param $_nntp RESOURCE handle to NNTP socket
     * @param $_id STRING MSGNUM or MSGID (a group should be selected in this case)  
     */
    function BananaPost(&$_nntp, $_id)
    {
        $this->nb = $_id;
        $this->_header($_nntp);

        $this->body = join("\n", $_nntp->body($_id));
        
        if (isset($this->headers['content-transfer-encoding'])) {
            if (preg_match("/base64/", $this->headers['content-transfer-encoding'])) {
                $this->body = base64_decode($this->body);
            } elseif (preg_match("/quoted-printable/", $this->headers['content-transfer-encoding'])) {
                $this->body = quoted_printable_decode($this->body);
            }
        }

        if (preg_match('!charset=([^;]*);!', $this->headers['content-type'], $matches)) {
            $this->body = iconv($matches[1], 'iso-8859-1', $this->body);
        }
    }

    function _header(&$_nntp)
    {
        global $news;
        $hdrs = $_nntp->head($this->nb);
        if (!$hdrs) {
            $this = null;
            return false;
        }

        // parse headers
        foreach ($hdrs as $line) {
            if (preg_match("/^[\t\r ]+/", $line)) {
                $line = ($hdr=="X-Face"?"":" ").ltrim($line);
                if (in_array($hdr, $news['head']))  {
                    $this->headers[$hdr] .= $line;
                }
            } else {
                list($hdr, $val) = split(":[ \t\r]*", $line, 2);
                $hdr = strtolower($hdr);
                if (in_array($hdr, $news['head'])) {
                    $this->headers[$hdr] = $val;
                }
            }
        }
        // decode headers
        foreach ($news['hdecode'] as $hdr) {
            if (isset($this->headers[$hdr])) {
                $this->headers[$hdr] = headerDecode($this->headers[$hdr]);
            }
        }

        $this->name = $this->headers['from'];
        $this->name = preg_replace('/<[^ ]*>/', '', $this->name);
        $this->name = trim($this->name);
    }
}

?>

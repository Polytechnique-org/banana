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
    var $id;
    /** headers */
    var $headers;
    /** body */
    var $body;
    /** poster name */
    var $name;

    /** constructor
     * @param $_id STRING MSGNUM or MSGID (a group should be selected in this case)  
     */
    function BananaPost($_id)
    {
        global $banana;
        $this->id = $_id;
        $this->_header();

        $this->body = join("\n", $banana->nntp->body($_id));
        
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

    function _header()
    {
        global $banana;
        $hdrs = $banana->nntp->head($this->id);
        if (!$hdrs) {
            $this = null;
            return false;
        }

        // parse headers
        foreach ($hdrs as $line) {
            if (preg_match("/^[\t\r ]+/", $line)) {
                $line = ($hdr=="X-Face"?"":" ").ltrim($line);
                if (in_array($hdr, $banana->parse_hdr))  {
                    $this->headers[$hdr] .= $line;
                }
            } else {
                list($hdr, $val) = split(":[ \t\r]*", $line, 2);
                $hdr = strtolower($hdr);
                if (in_array($hdr, $banana->parse_hdr)) {
                    $this->headers[$hdr] = $val;
                }
            }
        }
        // decode headers
        foreach ($banana->hdecode as $hdr) {
            if (isset($this->headers[$hdr])) {
                $this->headers[$hdr] = headerDecode($this->headers[$hdr]);
            }
        }

        $this->name = $this->headers['from'];
        $this->name = preg_replace('/<[^ ]*>/', '', $this->name);
        $this->name = trim($this->name);
    }

    function checkcancel() {
        if (function_exists('hook_checkcancel')) {
            return hook_checkcancel($this->headers);
        }
        return ($this->headers['from'] == $_SESSION['name']." <".$_SESSION['mail'].">");
    }

}

?>

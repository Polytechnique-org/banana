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

        if ($body = $banana->nntp->body($_id)) {
            $this->body = join("\n", $body);
        } else {
            return ($this = null);
        }
        
        if (isset($this->headers['content-transfer-encoding'])) {
            if (preg_match("/base64/", $this->headers['content-transfer-encoding'])) {
                $this->body = base64_decode($this->body);
            } elseif (preg_match("/quoted-printable/", $this->headers['content-transfer-encoding'])) {
                $this->body = quoted_printable_decode($this->body);
            }
        }

        if (preg_match('!charset=([^;]*)\s*(;|$)!', $this->headers['content-type'], $matches)) {
            require_once 'banana/misc.inc.php';
            $this->body = to_html($this->body, $matches[1]);
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

    function checkcancel()
    {
        if (function_exists('hook_checkcancel')) {
            return hook_checkcancel($this->headers);
        }
        return ($this->headers['from'] == $_SESSION['name']." <".$_SESSION['mail'].">");
    }

    function to_html()
    {
        global $banana;

        $res  = '<table class="bicol banana_msg" cellpadding="0" cellspacing="0">';
        $res .= '<tr><th colspan="2">'._b_('En-têtes').'</th></tr>';

        foreach ($banana->show_hdr as $hdr) {
            if (isset($this->headers[$hdr])) {
                $res2 = formatdisplayheader($hdr, $this->headers[$hdr]);
                if ($res2) {
                    $res .= '<tr><td class="hdr">'.header_translate($hdr)."</td><td class='val'>$res2</td></tr>\n";
                }
            }
        }

        $res .= '<tr><th colspan="2">'._b_('Corps').'</th></tr>';
        $res .= '<tr><td colspan="2"><pre>'.formatbody($this->body).'</pre></td></tr>';
        
        $res .= '<tr><th colspan="2">'._b_('apercu').'</th></tr>';
        $ndx  = $banana->spool->getndx($this->id);
        $res .= '<tr><td class="thrd" colspan="2">'.$banana->spool->to_html($ndx-$banana->tbefore, $ndx+$banana->tafter, $ndx).'</td></tr>';

        return $res.'</table>';
    }
}

?>

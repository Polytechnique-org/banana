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
    /** formating */
    var $messages;
    /** attachment */
    var $pj;
    /** poster name */
    var $name;

    /** constructor
     * @param $_id STRING MSGNUM or MSGID (a group should be selected in this case)  
     */
    function BananaPost($_id)
    {
        global $banana;
        $this->id       = $_id;
        $this->pj       = array();
        $this->messages = array();
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

        if (preg_match("@multipart/([^;]+);@", $this->headers['content-type'], $mpart_type)) {
            preg_match("/boundary=\"?([^ \"]+)\"?/", $this->headers['content-type'], $mpart_boundary);
            $this->split_multipart($mpart_type[1], $mpart_boundary[1]);
        }
        
        if (preg_match('!charset=([^;]*)\s*(;|$)!', $this->headers['content-type'], $matches)) {
            $this->body = iconv($matches[1], 'utf-8', $this->body);
        } else {
            $this->body = utf8_encode($this->body);
        }
    }

    /** split multipart messages
     * @param $type STRING multipart type description
     * @param $boundary STRING multipart boundary identification string
     */
    function split_multipart($type, $boundary)
    {
        $parts = preg_split("/\n--$boundary(--|\n)/", $this->body);
        foreach ($parts as $part) {
            $part = $this->get_part($part);
            $local_header = $part['headers'];
            $local_body = $part['body'];
            if (isset($local_header['content-disposition']) && preg_match("/attachment/", $local_header['content-disposition'])) {
                $this->add_attachment($part);
            } else if (isset($local_header['content-type']) && preg_match("@text/([^;]+);@", $local_header['content-type'], $format)) {
    	        array_push($this->messages, $part);
	        }
        }
        if (count($this->messages) > 0) {
            $this->set_body_to_part(0);
        }
    }

    /** extract new headers from the part
     * @param $part STRING part of a multipart message
     */
    function get_part($part)
    {
        global $banana;

        $lines = split("\n", $part);
        while (count($lines)) {
            $line = array_shift($lines);
            if ($line != "") {
                list($hdr, $val) = split(":[ \t\r]*", $line, 2);
                $hdr = strtolower($hdr);
                if (in_array($hdr, $banana->parse_hdr)) {
                    $local_headers[$hdr] = $val;
                }
            } else {
                break;
            }
        }
        return Array('headers' => $local_headers, 'body' => join("\n", $lines)); 
    }

    /** add an attachment
     */
    function add_attachment($part)
    {
        $local_header = $part['headers'];
        $local_body = $part['body'];

        if (!isset($local_header['content-transfer-encoding'])) {
            return false;
        }

        if (isset($local_header['content-disposition'])) {
            if (preg_match("/attachment/", $local_header['content-disposition'])) {
                preg_match("/filename=\"?([^\"]+)\"?/", $local_header['content-disposition'], $filename);
                $filename = $filename[1];
            }
        }
        if (!isset($filename)) {
            $filename = "attachment".count($pj);
        }

        if (isset($local_header['content-type'])) {
            if (preg_match("/^\\s*([^ ;]+);/", $local_header['content-type'], $mimetype)) {
                $mimetype = $mimetype[1];
            }
        }
        if (!isset($mimetype)) {
            return false;
        }

        array_push($this->pj, Array('MIME' => $mimetype,
                                    'filename' => $filename,
                                    'encoding' => strtolower($local_header['content-transfer-encoding']),
                                    'data' => $local_body));
        return true;
    }

    /** decode an attachment
     * @param pjid INT id of the attachment to decode
     * @param action BOOL action to execute : true=view, false=download
     */
    function get_attachment($pjid, $action = false)
    {
        if ($pjid >= count($this->pj)) {
            return false;
        } else {
            $file = $this->pj[$pjid];
            header('Content-Type: '.$file['MIME']);
            if (!$action) {
                header('Content-Disposition: attachment; filename="'.$file['filename'].'"');
            }
            if ($file['encoding'] == 'base64') {
                echo base64_decode($file['data']);
            } else {
                header('Content-Transfer-Encoding: '.$file['encoding']);
                echo $file['data'];
            }
            return true;
        }
    }

    /** set body to represent the given part
     * @param partid INT index of the part in messages
     */
    function set_body_to_part($partid)
    {
        global $banana;
        
        if (count($this->messages) == 0) {
            return false;
        }

        $local_header = $this->messages[$partid]['headers'];
        $this->body   = $this->messages[$partid]['body'];
        foreach ($banana->parse_hdr as $hdr) {
            if (isset($local_header[$hdr])) {
                $this->headers[$hdr] = $local_header[$hdr];
            }
        }
        return true;
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

    /** convert message to html
     * @param partid INT id of the multipart message that must be displaid
     */
    function to_html($partid = 0)
    {
        global $banana;

        if ($partid != 0) {
            $this->set_body_to_part($partid);
        }

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

        $res .= '<tr><th colspan="2">'._b_('Corps');
        if (count($this->messages) > 1) {
            for ($i = 0 ; $i < count($this->messages) ; $i++) {
                if ($i == 0) {
                    $res .= ' : ';
                } else {
                    $res .= ' . ';
                }
                preg_match("@text/([^;]+);@", $this->messages[$i]['headers']['content-type'], $format);
                $format = textFormat_translate($format[1]);
                if ($i != $partid) {
                    $res .= '<a href="?group='.$banana->state['group'].'&artid='.$this->id.'&part='.$i.'">'.$format.'</a>';
                } else {
                    $res .= $format;
                }
            }
        }
        $res .= '</th></tr>';
 
        preg_match("@text/([^;]+);@", $this->headers['content-type'], $format);
        $format = $format[1];
        $res .= '<tr><td colspan="2">';
        if ($format == 'html') {
            $res .= formatbody($this->body, $format); 
        } else {
            $res .= '<pre>'.formatbody($this->body).'</pre>';
        }
        $res .= '</td></tr>';

        if (count($this->pj) > 0) {
            $res .= '<tr><th colspan="2">'._b_('Pièces jointes').'</th></tr>';
            $res .= '<tr><td colspan="2">';
            $i = 0;
            foreach ($this->pj as $file) {
                $res .= $file['filename'].' ('.$file['MIME'].') : ';
                $res .= '<a href="pj.php?group='.$banana->state['group'].'&artid='.$this->id.'&pj='.$i.'">télécharger</a>';
                if (preg_match("@(image|text)/@", $file['MIME'])) {
                    $res .= ' . <a href="pj.php?group='.$banana->state['group'].'&artid='.$this->id.'&pj='.$i.'&action=view">aperçu</a>';
                }
                $res .=  '<br/>';
                $i++;
            }
            $res .= '</td></tr>';
        }
        
        $res .= '<tr><th colspan="2">'._b_('apercu').'</th></tr>';
        $ndx  = $banana->spool->getndx($this->id);
        $res .= '<tr><td class="thrd" colspan="2">'.$banana->spool->to_html($ndx-$banana->tbefore, $ndx+$banana->tafter, $ndx).'</td></tr>';

        return $res.'</table>';
    }
}

?>

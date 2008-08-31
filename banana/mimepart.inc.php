<?php
/********************************************************************************
* banana/mimepart.inc.php : class for MIME parts
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

class BananaMimePart
{
    public $headers      = null; /* Should be protected */

    private $id           = null;
    private $content_type = null;
    private $charset      = null;
    private $encoding     = null;
    private $disposition  = null;
    private $boundary     = null;
    private $filename     = null;
    private $format       = null;
    private $signature    = array();

    private $body         = null;
    private $multipart    = null;

    protected function __construct($data = null)
    {
        if ($data instanceof BananaMimePart) {
            foreach ($this as $key=>$value) {
                $this->$key = $data->$key;
            }
        } elseif (!is_null($data)) {
            $this->fromRaw($data);
        }
    }

    protected function makeTextPart($body, $content_type, $encoding, $charset = null, $format = 'fixed')
    {
        $this->body         = $body;
        $this->charset      = $charset;
        $this->encoding     = $encoding;
        $this->content_type = $content_type;
        $this->format       = strtolower($format);
        $this->parse();
    }

    protected function makeDataPart($body, $content_type, $encoding, $filename, $disposition, $id = null)
    {
        $this->body         = $body;
        $this->content_type = $content_type;
        $this->encoding     = $encoding;
        $this->filename     = $filename;
        $this->disposition  = $disposition;
        $this->id           = $id;
        if (is_null($content_type) || $content_type == 'application/octet-stream') {
            $this->decodeContent();
            $this->content_type = BananaMimePart::getMimeType($this->body, false);
        }
    }

    protected function makeFilePart(array $file, $content_type = null, $disposition = 'attachment')
    {
        $body = file_get_contents($file['tmp_name']);
        if ($body === false || strlen($body) != $file['size']) {
            return false;
        }
        if (is_null($content_type) || $content_type == 'application/octet-stream') {
            $content_type = BananaMimePart::getMimeType($file['tmp_name']);
        }
        if (substr($content_type, 0, 5) == 'text/') {
            $encoding = '8bit';
        } else {
            $encoding = 'base64';
            $body     = chunk_split(base64_encode($body));
        }
        $this->filename     = $file['name'];
        $this->content_type = $content_type;
        $this->disposition  = $disposition;
        $this->body         = $body;
        $this->encoding     = $encoding;
        return true;
    }

    protected function makeMultiPart($body, $content_type, $encoding, $boundary, $sign_protocole)
    {
        $this->body         = $body;
        $this->content_type = $content_type;
        $this->encoding     = $encoding;
        $this->boundary     = $boundary;
        $this->signature['protocole'] = $sign_protocole;
        $this->parse();
    }

    protected function convertToMultiPart()
    {
        if (!$this->isType('multipart', 'mixed')) {
            $newpart = new BananaMimePart($this);
            $this->content_type = 'multipart/mixed';
            $this->encoding     = '8bit';
            $this->multipart    = array($newpart);
            $this->headers      = null;
            $this->charset      = null;
            $this->disposition  = null;
            $this->filename     = null;
            $this->boundary     = null;
            $this->body         = null;
            $this->format       = null;
            $this->id           = null;
        } 
    }

    public function addAttachment(array $file, $content_type = null, $disposition = 'attachment')
    {
        if (!is_uploaded_file($file['tmp_name'])) {
            return false;
        }
        $newpart = new BananaMimePart;
        if ($newpart->makeFilePart($file, $content_type, $disposition)) {
            $this->convertToMultiPart();
            $this->multipart[] = $newpart;
            return true;
        }
        return false;
    }

    protected function getHeader($title, $filter = null)
    {
        if (!isset($this->headers[$title])) {
            return null;
        }
        $header =& $this->headers[$title];
        if (is_null($filter)) {
            return trim($header);
        } elseif (preg_match($filter, $header, $matches)) {
            return trim($matches[1]);
        }
        return null;
    } 

    protected function fromRaw($data)
    {
        if (is_array($data)) {
            if (array_key_exists('From', $data)) {
                $this->headers = array_map(array($this, 'decodeHeader'), array_change_key_case($data));
                return;
            } else {
                $lines = $data;
            }
        } else {
            $lines   = explode("\n", $data);
        }
        $headers = BananaMimePart::parseHeaders($lines);
        $this->headers =& $headers;
        if (empty($headers) || empty($lines)) {
            return;
        }
        $content       = join("\n", $lines);
        $test          = trim($content);
        if (empty($test)) {
            return;
        }

        $content_type = strtolower($this->getHeader('content-type', '/^\s*([^ ;]+?)(;|$)/'));
        if (empty($content_type)) {
            $encoding     = '8bit';
            $charset      = 'CP1252';
            $content_type = 'text/plain';
            $format       = strtolower($this->getHeader('x-rfc2646', '/format="?([^ w@"]+?)"?\s*(;|$)/i'));
        } else {
            $encoding     = strtolower($this->getHeader('content-transfer-encoding'));
            $disposition  = $this->getHeader('content-disposition', '/(inline|attachment)/i');
            $boundary     = $this->getHeader('content-type', '/boundary="?([^ "]+?)"?\s*(;|$)/i');
            $charset      = strtolower($this->getHeader('content-type', '/charset="?([^ "]+?)"?\s*(;|$)/i'));
            $filename     = $this->getHeader('content-disposition', '/filename="?([^ "]+?)"?\s*(;|$)/i');
            $format       = strtolower($this->getHeader('content-type', '/format="?([^ "]+?)"?\s*(;|$)/i'));
            $id           = $this->getHeader('content-id', '/<(.*?)>/');
            $sign_protocole = strtolower($this->getHeader('content-type', '/protocol="?([^ "]+?)"?\s*(;|$)/i'));
            if (empty($filename)) {
                $filename = $this->getHeader('content-type', '/name="?([^"]+)"?/');
            }
        }
        list($type, $subtype) = explode('/', $content_type);
        switch ($type) {
          case 'text': case 'message':
            $this->makeTextPart($content, $content_type, $encoding, $charset, $format);
            break;
          case 'multipart':
            $this->makeMultiPart($content, $content_type, $encoding, $boundary, $sign_protocole);
            break;
          default:
            $this->makeDataPart($content, $content_type, $encoding, $filename, $disposition, $id);
        }
    }

    private function parse()
    {
        if ($this->isType('multipart')) {
            $this->splitMultipart();
        } else {
            $parts = $this->findUUEncoded();
            if (count($parts)) {
                $this->convertToMultiPart();
                $this->multipart = array_merge($this->multipart, $parts);
                // Restore "message" headers to the previous level"
                $this->headers = array();
                foreach (Banana::$msgshow_headers as $hdr) {
                    if (isset($this->multipart[0]->headers[$hdr])) {
                        $this->headers[$hdr] = $this->multipart[0]->headers[$hdr];
                    }
                }
            }
        }
    }

    private function splitMultipart()
    {
        $this->decodeContent();
        if (is_null($this->multipart)) {
            $this->multipart = array();
        }
        $boundary =& $this->boundary;
        $parts = preg_split("/(^|\n)--" . preg_quote($boundary, '/') . "(--|\n)/", $this->body, -1, PREG_SPLIT_NO_EMPTY);
        $signed = $this->isType('multipart', 'signed');
        $signature = null;
        $signed_message = null;
        foreach ($parts as &$part) {
            $newpart = new BananaMimePart($part);
            if (!is_null($newpart->content_type)) {
                if ($signed && $newpart->content_type == $this->signature['protocole']) { 
                    $signature = $newpart->body;
                } elseif ($signed) { 
                    $signed_message = $part; 
                } 
                $this->multipart[] = $newpart;
            }
        }
        if ($signed) {
            $this->checkPGPSignature($signature, $signed_message);
        }
        $this->body = null;
    }

    public static function getMimeType($data, $is_filename = true)
    {
        if ($is_filename) {
            $type = mime_content_type($data);
            if ($type == 'text/plain') { // XXX Workaround a bug of php 5.2.0+etch10 (fallback for mime_content_type is text/plain)
                $type = preg_replace('/;.*/', '', trim(shell_exec('file -bi ' . escapeshellarg($data))));
            }
        } else {
            $arg = escapeshellarg($data);
            $type = preg_replace('/;.*/', '', trim(shell_exec("echo $arg | file -bi -")));
        }
        return empty($type) ? 'application/octet-stream' : $type;
    }

    private function findUUEncoded()
    {
        $this->decodeContent();
        $parts = array(); 
        if (preg_match_all("/\n(begin \d+ ([^\r\n]+)\r?(?:\n(?!end)[^\n]*)*\nend)/",
            $this->body, $matches, PREG_SET_ORDER)) {
            foreach ($matches as &$match) {
                $data = convert_uudecode($match[1]);
                $mime = BananaMimePart::getMimeType($data, false);
                if ($mime != 'application/x-empty') {
                    $this->body = trim(str_replace($match[0], '', $this->body));
                    $newpart = new BananaMimePart;
                    self::decodeHeader($match[2]);
                    $newpart->makeDataPart($data, $mime, '8bit', $match[2], 'attachment');
                    $parts[] = $newpart;
                }
            }
        }
        return $parts;
    }

    static private function _decodeHeader($charset, $c, $str)
    {
        $s = ($c == 'Q' || $c == 'q') ? quoted_printable_decode($str) : base64_decode($str);
        $s = @iconv($charset, 'UTF-8', $s);
        return str_replace('_', ' ', $s);
    }

    static public function decodeHeader(&$val, $key = null)
    {
        if (preg_match('/[\x80-\xff]/', $val)) {
            if (!is_utf8($val)) {
                $val = utf8_encode($val);
            }
        } elseif (strpos($val, '=') !== false) {
            $val = preg_replace('/(=\?.*?\?[bq]\?.*?\?=) (=\?.*?\?[bq]\?.*?\?=)/i', '\1\2', $val);
            $val = preg_replace('/=\?(.*?)\?([bq])\?(.*?)\?=/ie', 'BananaMimePart::_decodeHeader("\1", "\2", "\3")', $val);
        }
    }

    static public function &parseHeaders(array &$lines)
    {
        $headers = array();
        while ($lines) {
            $line = array_shift($lines);
            if (isset($hdr) && $line && ctype_space($line{0})) {
                $headers[$hdr] .= ' ' . trim($line);
            } elseif (!empty($line)) {
                if (strpos($line, ':') !== false) {
                    list($hdr, $val) = explode(":", $line, 2);
                    $hdr = strtolower($hdr);
                    if (in_array($hdr, Banana::$msgparse_headers)) {  
                        $headers[$hdr] = ltrim($val);
                    } else {
                        unset($hdr);
                    }
                }
            } else {
                break;
            }
        }
        array_walk($headers, array('BananaMimePart', 'decodeHeader'));
        return $headers;
    }

    static public function encodeHeader($value, $trim = 0)
    {
        if ($trim) {
            if (strlen($value) > $trim) {
                $value = substr($value, 0, $trim);
            }
        }
        $value = preg_replace('/([\x80-\xff]+)/e', '"=?UTF-8?B?" . base64_encode("\1") . "?="', $value);
        return $value;
    }

    private function decodeContent()
    {
        $encodings = Array('quoted-printable' => 'quoted_printable_decode',
                           'base64'           => 'base64_decode',
                           'x-uuencode'       => 'convert_uudecode');
        foreach ($encodings as $encoding => $callback) {
            if ($this->encoding == $encoding) {
                $this->body     = $callback($this->body);
                $this->encoding = '8bit';
                break;
            }
        }
        if (!$this->isType('text')) {
            return;
        }

        if (!is_null($this->charset)) {
            $body = @iconv($this->charset, 'UTF-8//IGNORE', $this->body);
            if (empty($body)) {
                return;
            }
            $this->body = $body;
        } else {
            $this->body = utf8_encode($this->body);
        }
        $this->charset = 'utf-8';
    }

    public function send($force_inline = false)
    {
        $this->decodeContent();
        if ($force_inline) {
            $dispostion =  $this->disposition;
            $this->disposition = 'inline';
        }
        $headers = $this->getHeaders();
        foreach ($headers as $key => $value) {
            header("$key: $value");
        }
        if ($force_inline) {
            $this->disposition = $disposition;
        }
        echo $this->body;
        exit;
    }

    private function setBoundary()
    {
        if ($this->isType('multipart') && is_null($this->boundary)) {
            $this->boundary = '--banana-bound-' . time() . rand(0, 255) . '-';
        }
    }

    public function getHeaders()
    {
        $headers = array();
        $this->setBoundary();
        $headers['Content-Type'] = $this->content_type . ";"
            . ($this->filename ? " name=\"{$this->filename}\";" : '')
            . ($this->charset ? " charset=\"{$this->charset}\";" : '')
            . ($this->boundary ? " boundary=\"{$this->boundary}\";" : "")
            . ($this->format ? " format={$this->format}" : "");
        if ($this->encoding) {
            $headers['Content-Transfer-Encoding'] = $this->encoding;
        }
        if ($this->disposition) {
            $headers['Content-Disposition'] = $this->disposition
                . ($this->filename ? "; filename=\"{$this->filename}\"" : '');
        }
        return array_map(array($this, 'encodeHeader'), $headers);
    }

    public function hasBody()
    {
        if (is_null($this->content) && !$this->isType('multipart')) {
            return false;
        }
        return true;
    }

    public function get($with_headers = false)
    {
        $content = "";
        if ($with_headers) {
            foreach ($this->getHeaders() as $key => $value) {
                $line = "$key: $value"; 
                $line = explode("\n", wordwrap($line, Banana::$msgshow_wrap));
                for ($i = 1 ; $i < count($line) ; $i++) {
                    $line[$i] = "\t" . $line[$i];
                }
                $content .= implode("\n", $line) . "\n";
            } 
            $content .= "\n";
        } 
        if ($this->isType('multipart')) {
            $this->setBoundary();
            foreach ($this->multipart as &$part) {
                $content .= "\n--{$this->boundary}\n" . $part->get(true);
            }
            $content .= "\n--{$this->boundary}--";
        } elseif ($this->isType('text', 'plain')) {
            $content .= banana_flow($this->body);
        } else {
            $content .= banana_wordwrap($this->body);
        }
        return $content;
    }

    public function getText()
    {
        $signed =& $this->getSignedPart(); 
        if ($signed !== $this) { 
            return $signed->getText(); 
        }
        $this->decodeContent();
        return $this->body;
    }

    public function toHtml()
    {
        $signed =& $this->getSignedPart(); 
        if ($signed !== $this) { 
            return $signed->toHtml(); 
        }
        @list($type, $subtype) = $this->getType();
        if ($type == 'image') {
            $part = $this->id ? $this->id : $this->filename;
            return '<img class="multipart" src="'
                 . banana_htmlentities(Banana::$page->makeUrl(array('group' => Banana::$group,
                                                                    'artid' => Banana::$artid,
                                                                    'part'  => $part)))
                 . '" alt="' . banana_htmlentities($this->filename) . '" />';
        } else if ($type == 'multipart' && $subtype == 'alternative') {
            $types =& Banana::$msgshow_mimeparts;
            foreach ($types as $type) {
                @list($type, $subtype) = explode('/', $type);
                $part = $this->getParts($type, $subtype);
                if (count($part) > 0) {
                    return $part[0]->toHtml();
                }
            }
        } elseif ((!in_array($type, Banana::$msgshow_mimeparts)
                  && !in_array($this->content_type, Banana::$msgshow_mimeparts))
                  || $this->disposition == 'attachment') {
            $part = $this->id ? $this->id : $this->filename;
            if (!$part) {
                $part = $this->content_type;
            }
            return '[' . Banana::$page->makeImgLink(array('group' => Banana::$group,
                                                 'artid' => Banana::$artid,
                                                 'part'  => $part,
                                                 'text'  => $this->filename ? $this->filename : $this->content_type,
                                                 'img'   => 'save')) . ']';
        } elseif ($type == 'multipart' && ($subtype == 'mixed' || $subtype == 'report')) {
            $text = '';
            foreach ($this->multipart as &$part) {
                $text .= $part->toHtml();
            }
            return $text;
        } else {
            switch ($subtype) {
              case 'html': return banana_formatHtml($this);
              case 'enriched': case 'richtext': return banana_formatRichText($this);
              default:
                if ($type == 'message') { // we have a raw source of data (no specific pre-formatting)
                    return '<hr />' . utf8_encode(banana_formatPlainText($this));
                }
                return banana_formatPlainText($this);
            }
        }
        return null;
    }

    public function quote()
    {
        $signed =& $this->getSignedPart();
        if ($signed !== $this) {
            return $signed->quote();
        }
        list($type, $subtype) = $this->getType();
        if (in_array($type, Banana::$msgedit_mimeparts) || in_array($this->content_type, Banana::$msgedit_mimeparts)) {
            if ($type == 'multipart' && ($subtype == 'mixed' || $subtype == 'report')) {
                $text = '';
                foreach ($this->multipart as &$part) {
                    $qt = $part->quote();
                    $qt = rtrim($qt);
                    if (!empty($text)) {
                        $text .= "\n" . banana_quote("", 1) . "\n";
                    }
                    $text .= $qt;
                }
                return $text;
            }
            switch ($subtype) {
              case 'html': return banana_quoteHtml($this);
              case 'enriched': case 'richtext': return banana_quoteRichText($this);
              default: return banana_quotePlainText($this);
            }
        }
    }

    protected function getType()
    {
        return explode('/', $this->content_type);
    }

    protected function isType($type, $subtype = null)
    {
        list($mytype, $mysub) = $this->getType();
        return ($mytype == $type) && (is_null($subtype) || $mysub == $subtype);
    }

    public function isFlowed()
    {
        return $this->format == 'flowed';
    }

    public function getFilename()
    {
        return $this->filename;
    }

    protected function getParts($type, $subtype = null)
    {
        $parts = array();
        if ($this->isType($type, $subtype)) {
            return array($this);
        } elseif ($this->isType('multipart')) {
            foreach ($this->multipart as &$part) {
                $parts = array_merge($parts, $part->getParts($type, $subtype));
            }
        }
        return $parts;
    }

    public function getFile($filename)
    {
        if ($this->filename == $filename) {
            return $this;
        } elseif ($this->isType('multipart')) {
            foreach ($this->multipart as &$part) {
                $file = $part->getFile($filename);
                if (!is_null($file)) {
                    return $file;
                }
            }
        }
        return null;
    }

    public function getAttachments()
    {
        if (!is_null($this->filename)) {
            return array($this);
        } elseif ($this->isType('multipart')) {
            $parts = array();
            foreach ($this->multipart as &$part) {
                $parts = array_merge($parts, $part->getAttachments());
            }
            return $parts;
        }
        return array();
    }

    public function getAlternatives()
    {
        $types =& Banana::$msgshow_mimeparts;
        $names =& Banana::$mimeparts;
        $source = null;
        if (in_array('source', $types)) {
            $source = @$names['source'] ? $names['source'] : 'source';
        }
        if ($this->isType('multipart', 'signed')) {
            $parts = array($this->getSignedPart());
        } else if (!$this->isType('multipart', 'alternative') && !$this->isType('multipart', 'related')) {
            if ($source) {
                $parts = array($this);
            } else {
                return array();
            }
        } else {
            $parts =& $this->multipart;
        }
        $alt = array();
        foreach ($parts as &$part) {
            list($type, $subtype) = $part->getType();
            $ct = $type . '/' . $subtype;
            if (in_array($ct, $types) || in_array($type, $types)) {
                if (isset($names[$ct])) {
                    $alt[$ct] = $names[$ct];
                } elseif (isset($names[$type])) {
                    $alt[$ct] = $names[$type];
                } else {
                    $alt[$ct] = $ct;
                }
            }
        }
        if ($source) {
            $alt['source'] = $source;
        }
        return $alt;
    }

    public function getPartById($id)
    {
        if ($this->id == $id) {
            return $this;
        } elseif ($this->isType('multipart')) {
            foreach ($this->multipart as &$part) {
                $res = $part->getPartById($id);
                if (!is_null($res)) {
                    return $res;
                }
            }
        }
        return null;
    }

    protected function &getSignedPart()
    {
        if ($this->isType('multipart', 'signed')) {
            foreach ($this->multipart as &$part) {
                if ($part->content_type != $this->signature['protocole']) {
                    return $part;
                }
            }
        }
        return $this;
    }

    private function checkPGPSignature($signature, $message = null)
    {
        if (!Banana::$msgshow_pgpcheck) {
            return true;
        }
        $signname = tempnam(Banana::$spool_root, 'banana_pgp_');
        $gpg = 'LC_ALL="en_US" ' . Banana::$msgshow_pgppath . ' ' . Banana::$msgshow_pgpoptions . ' --verify '
                .  $signname . '.asc ';
        file_put_contents($signname. '.asc', $signature);
        $gpg_check = array();
        if (!is_null($message)) {
            file_put_contents($signname, str_replace(array("\r\n", "\n"), array("\n", "\r\n"), $message));
            exec($gpg . $signname . ' 2>&1', $gpg_check, $result);
            unlink($signname);
        } else {
            exec($gpg . '2&>1', $gpg_check, $result);
        }
        unlink("$signname.asc");
        if (preg_match('/Signature made (.+) using (.+) key ID (.+)/', array_shift($gpg_check), $matches)) {
            $this->signature['date'] = strtotime($matches[1]);
            $this->signature['key'] = array('format' => $matches[2],
                                            'id'     => $matches[3]);
        } else {
            return false;
        }
        $signature = array_shift($gpg_check);
        if (preg_match('/Good signature from "(.+)"/', $signature, $matches)) {
            $this->signature['verify'] = true;
            $this->signature['identity'] = array($matches[1]);
            $this->signature['certified'] = true;
        } elseif (preg_match('/BAD signature from "(.+)"/', $signature, $matches)) {
            $this->signature['verify'] = false;
            $this->signature['identity'] = array($matches[1]);
            $this->signature['certified'] = false;
        } else {
            return false;
        }
        foreach ($gpg_check as $aka) {
            if (preg_match('/aka "(.+)"/', $aka, $matches)) {
                $this->signature['identity'][] = $matches[1];
            }
            if (preg_match('/This key is not certified with a trusted signature!/', $aka)) {
                $this->signature['certified'] = false;
                $this->signature['certification_error'] = _b_("identité non confirmée");
            }
        }
        return true;
    }

    public function getSignature()
    {
        return $this->signature;
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

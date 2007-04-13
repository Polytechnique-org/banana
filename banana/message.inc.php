<?php
/********************************************************************************
* banana/message.inc.php : class for messages
* ------------------------
*
* This file is part of the banana distribution
* Copyright: See COPYING files that comes with this distribution
********************************************************************************/

require_once dirname(__FILE__) . '/mimepart.inc.php';
require_once dirname(__FILE__) . '/message.func.inc.php';
require_once dirname(__FILE__) . '/banana.inc.php';

final class BananaMessage extends BananaMimePart
{
    private $msg_headers = array();

    public function __construct($data = null)
    {
        parent::__construct($data);
        if (!is_null($data)) {
            if (isset($this->headers['in-reply-to']) && isset($this->headers['references'])) {
                unset($this->headers['in-reply-to']);
            }
            Banana::$msgshow_headers = array_intersect(Banana::$msgshow_headers, array_keys($this->headers));
            Banana::$message =& $this;
        }
    }

    public function hasHeader($hdr)
    {
        return isset($this->headers[$hdr]);
    }

    static public function newMessage(array $headers, $body, array $file = null)
    {
        $msg = new BananaMessage();
        $msg->msg_headers = $headers;
        $msg->makeTextPart($body, 'text/plain', '8bits', 'UTF-8', 'fixed');
        if (!is_null($file)) {
            $msg->addAttachment($file);
        }
        return $msg;
    }

    static public function translateHeaderName($hdr)
    {
        switch (strtolower($hdr)) {
          case 'from':          return _b_('De');
          case 'subject':       return _b_('Sujet');
          case 'newsgroups':    return _b_('Forums');
          case 'followup-to':   return _b_('Suivi à');
          case 'to':            return _b_('À');
          case 'cc':            return _b_('Copie à');
          case 'bcc':           return _b_('Copie cachée à');
          case 'reply-to':      return _b_('Répondre à');
          case 'date':          return _b_('Date');
          case 'organization':  return _b_('Organisation');
          case 'in-reply-to':
          case 'references':    return _b_('Références');
          case 'x-face':        return _b_('Image');
        }
        return $hdr;
    }

    public function translateHeaderValue($hdr)
    {
        if (!isset($this->headers[$hdr])) {
            return null;
        }
        $text = $this->headers[$hdr];

        if (function_exists('hook_formatDisplayHeader')
             && $res = hook_formatDisplayHeader($hdr, $text)) {
            return $res;
        }
        switch ($hdr) {
          case "date":
            return BananaMessage::formatDate($text);

          case "followup-to": case "newsgroups":
            $groups = preg_split("/[\t ]*,[\t ]*/", $text);
            $res    = '';
            foreach ($groups as $g) {
                $res .= Banana::$page->makeLink(Array('group' => $g, 'text' => $g)) . ', ';
            }
            return substr($res,0, -2);

          case "from":
            return BananaMessage::formatFrom($text, $this->headers['subject']);

          case "references": case "in-reply-to":
            $rsl     = "";
            $parents = preg_grep('/^\d+$/', $this->getTranslatedReferences());
            $p       = array_pop($parents);

            $parents = array();
            while (!is_null($p)) {
                array_unshift($parents, $p);
                $p         = Banana::$spool->overview[$p]->parent;  
            }
            $ndx = 1;
            foreach ($parents as $p) {
                $rsl .= Banana::$page->makeLink(Array('group' => Banana::$spool->group,
                                                      'artid' => $p, 'text' => $ndx++)) . ' ';
            }
            return $rsl;

          case "subject":
            $text = stripslashes($text);
            $text = banana_htmlentities($text);
            return banana_catchFormats($text);

          default:
            return $text;
        }
    }

    public function getSender()
    {
        $from = $this->headers['from'];
        $name = trim(preg_replace('/<[^ ]*>/', '', $from));
        if (empty($name)) {
            return $from;
        }
        return $name;
    }

    public function getHeaderValue($hdr)
    {
        $hdr = strtolower($hdr);
        if (!isset($this->headers[$hdr])) {
            return null;
        }
        if ($hdr == 'date') {
            return strtotime($this->headers['date']);
        } else {
            return $this->headers[$hdr];
        }
    }

    public function getHeaders()
    {
        $this->msg_headers = array_merge($this->msg_headers, Banana::$msgedit_headers, Banana::$profile['headers']);
        $headers = array_map(array($this, 'encodeHeader'), $this->msg_headers);
        return array_merge($headers, parent::getHeaders());
    }

    static public function formatFrom($text, $subject = '')
    {
#     From: mark@cbosgd.ATT.COM
#     From: <mark@cbosgd.ATT.COM>
#     From: mark@cbosgd.ATT.COM (Mark Horton)
#     From: Mark Horton <mark@cbosgd.ATT.COM>
        $mailto = '<a href="mailto:';

        $result = banana_htmlentities($text);
        if ($subject) {
           $subject = '?subject=' . banana_htmlentities(_b_('Re: ') . $subject, ENT_QUOTES);
        }
        if (preg_match("/^<?([^< ]+@[^> ]+)>?$/", $text, $regs)) {
            $result = $mailto . $regs[1] . $subject . '">' . banana_htmlentities($regs[1]) . '</a>';
        }
        if (preg_match("/^([^ ]+@[^ ]+) \((.*)\)$/", $text, $regs)) {
            $result = $mailto . $regs[1] . $subject . '">' . banana_htmlentities($regs[2]) . '</a>';
        }   
        if (preg_match("/^\"?([^<>\"]+)\"? +<(.+@.+)>$/", $text, $regs)) {
            $nom = preg_replace("/^'(.*)'$/", '\1', $regs[1]);
            $nom = stripslashes($nom);
            $result = $mailto . $regs[2] . $subject . '">' . banana_htmlentities($nom) . '</a>';
        }
        return preg_replace("/\\\(\(|\))/","\\1",$result);
    }

    public function getAuthorName()
    {
        $text = $this->getHeaderValue('From');
        $name = null;
        if (preg_match("/^([^ ]+@[^ ]+) \((.*)\)$/", $text, $regs)) {
            $name = $regs[2];
        }   
        if (preg_match("/^\"?([^<>\"]+)\"? +<(.+@.+)>$/", $text, $regs)) {
            $name = preg_replace("/^'(.*)'$/", '\1', $regs[1]);
            $name = stripslashes($name);
        }
        if ($name) {
            return preg_replace("/\\\(\(|\))/","\\1", $name);
        }

        if (function_exists('hook_getAuthorName') && $name = hook_getAuthorName($this)) {
            return $name;
        }

        if (preg_match("/([^< ]+)@([^> ]+)/", $text, $regs)) {
            return $regs[1];
        }
        return 'Anonymous';
    }

    static public function formatDate($text)
    {
        return strftime("%A %d %B %Y, %H:%M (fuseau serveur)", strtotime($text));
    }

    public function translateHeaders()
    {
        $result = array();
        foreach (array_keys($this->headers) as $name) {
            $value = $this->translateHeaderValue($name);
            if (!is_null($value)) {
                $result[$this->translateHeaderName($name)] = $value;
            }
        }
        return $result;
    }

    public function getReferences()
    {
        $text = $this->headers['references'];
        $text = str_replace("><","> <", $text);
        return preg_split('/\s/', $text);
    }

    public function getTranslatedReferences()
    {
        return BananaMessage::formatReferences($this->headers);
    }

    static public function formatReferences(array &$refs)
    {
        if (isset($refs['references'])) {
            $text = str_replace('><', '> <', $refs['references']);
            return preg_split('/\s/', strtr($text, Banana::$spool->ids));
        } elseif (isset($refs['in-reply-to']) && isset(Banana::$spool->ids[$refs['in-reply-to']])) {
            return array(Banana::$spool->ids[$refs['in-reply-to']]);
        } else {
            return array();
        }
    }

    public function hasXFace()
    {
        return Banana::$msgshow_xface && isset($this->headers['x-face']);
    }

    public function getXFace()
    {
        header('Content-Type: image/gif');
        $xface = $this->headers['x-face'];
        passthru('echo ' . escapeshellarg($xface)
                . '| uncompface -X '
                . '| convert -transparent white xbm:- gif:-');
        exit;
    }

    public function getFormattedBody(&$reqtype = null)
    {
        $types = Banana::$msgshow_mimeparts;
        if (!is_null($reqtype)) {
            array_unshift($types, $reqtype);
        }
        foreach ($types as $type) {
            @list($type, $subtype) = explode('/', $type);
            $parts = $this->getParts($type, $subtype);
            if (empty($parts)) {
                continue;
            }
            $reqtype = implode('/', $parts[0]->getType());
            return $parts[0]->toHtml();
        }
        return null;
    }

    public function quote()
    {
        foreach (Banana::$msgedit_mimeparts as $type) {
            @list($type, $subtype) = explode('/', $type);
            $parts = $this->getParts($type, $subtype);
            if (empty($parts)) {
                continue;
            }
            if ($parts[0] === $this) {
                return parent::quote();
            }
            return $parts[0]->quote();
        }
        return null;
    }

    public function canCancel()
    {
        if (!Banana::$protocole->canCancel()) {
            return false;
        }
        if (function_exists('hook_checkcancel')) {
            return hook_checkcancel($this->headers);
        }
        return Banana::$profile['name'] == $this->headers['from'];
    }

    public function canSend()
    {
        return Banana::$protocole->canSend();
    }
}

// vim:set et sw=4 sts=4 ts=4 enc=utf-8:
?>

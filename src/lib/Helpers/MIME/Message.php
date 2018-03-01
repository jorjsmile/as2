<?php

namespace Jorjsmile\AS2\Helpers\MIME;
use Jorjsmile\AS2\Helpers\MIME;

/**
 * The Message:: class provides methods for creating and manipulating
 * MIME email messages.
 *
 * $Horde: framework/MIME/MIME/Message.php,v 1.76.10.19 2009/01/06 15:23:20 jan Exp $
 *
 * Copyright 1999-2009 The Horde Project (http://www.horde.org/)
 *
 * See the enclosed file COPYING for license information (LGPL). If you
 * did not receive this file, see http://www.fsf.org/copyleft/lgpl.html.
 *
 * @author  Chuck Hagenbuch <chuck@horde.org>
 * @author  Michael Slusarz <slusarz@horde.org>
 * @package MIME
 */
class Message extends Part {

    /**
     * Has the message been parsed via buildMessage()?
     *
     * @var boolean
     */
    protected $_build = false;

    /**
     * The server to default unqualified addresses to.
     *
     * @var string
     */
    protected $_defaultServer = null;

    /**
     * Constructor - creates a new MIME email message.
     *
     * @param string $defaultServer  The server to default unqualified
     *                               addresses to.
     */
    public function __construct($defaultServer = null)
    {
        if (is_null($defaultServer) && isset($_SERVER['SERVER_NAME'])) {
            $this->_defaultServer = $_SERVER['SERVER_NAME'];
        } else {
            $this->_defaultServer = $defaultServer;
        }
    }

    /**
     * Create a Message object from a Part object.
     * This public function can be called statically via:
     *    Message::convertMIMEPart();
     *
     * @param Part &$Part  The Part object.
     * @param string $server         The server to default unqualified
     *                               addresses to.
     *
     * @return Message  The new Message object.
     */
    public function &convertMIMEPart(&$Part, $server = null)
    {
        if (!$Part->getMIMEId()) {
            $Part->setMIMEId(1);
        }

        $Message = new Message($server);
        $Message->addPart($Part);
        $Message->buildMessage();

        return $Message;
    }

    /**
     * Take a set of headers and make sure they are encoded properly.
     *
     * @param array $headers   The headers to encode.
     * @param string $charset  The character set to use.
     *
     * @return array  The array of encoded headers.
     */
    public function encode($headers, $charset)
    {
        $addressKeys = array('To', 'Cc', 'Bcc', 'From');
        $asciikeys = array('MIME-Version', 'Received', 'Message-ID', 'Date', 'Content-Disposition', 'Content-Transfer-Encoding', 'Content-ID', 'Content-Type', 'Content-Description');
        foreach ($headers as $key => $val) {
            if (is_array($val)) {
                foreach ($val as $key2 => $val2) {
                    $headers[$key][$key2] = MIME::wrapHeaders($key, $val2, $this->getEOL());
                }
            } else {
                if (in_array($key, $addressKeys)) {
                    $text = MIME::encodeAddress($val, $charset, $this->_defaultServer);
                } else {
                    $text = MIME::encode($val, in_array($key, $asciikeys) ? 'US-ASCII' : $charset);
                }
                $headers[$key] = MIME::wrapHeaders($key, $text, $this->getEOL());
            }
        }

        return $headers;
    }

    /**
     * Add the proper set of MIME headers for this message to an array.
     *
     * @param array $headers  The headers to add the MIME headers to.
     *
     * @return array  The full set of headers including MIME headers.
     */
    public function header($headers = array())
    {
        /* Per RFC 2045 [4], this MUST appear in the message headers. */
        $headers['MIME-Version'] = '1.0';

        if ($this->_build) {
            return parent::header($headers);
        } else {
            $this->buildMessage();
            return $this->encode($this->header($headers), $this->getCharset());
        }
    }

    /**
     * Return the entire message contents, including headers, as a string.
     *
     * @return string  The encoded, generated message.
     */
    public function toString($headers = false)
    {
        if ($this->_build) {
            return parent::toString($headers);
        } else {
            $this->buildMessage();
            return $this->toString($headers);
        }
    }

    /**
     * Build message from current contents.
     */
    public function buildMessage()
    {
        if ($this->_build) {
            return;
        }

        if (empty($this->_flags['setType'])) {
            if (count($this->_parts) > 1) {
                $this->setType('multipart/mixed');
            } else {
                /* Copy the information from the single part to the current
                   base part. */
                if (($obVars = get_object_vars(reset($this->_parts)))) {
                    foreach ($obVars as $key => $val) {
                        $this->$key = $val;
                    }
                }
            }
        }

        /* Set the build flag now. */
        $this->_build = true;
    }

    /**
     * Get a list of all MIME subparts.
     *
     * @return array  An array of the Part subparts.
     */
    public function getParts()
    {
        if ($this->_build) {
            return parent::getParts();
        } else {
            $this->buildMessage();
            return $this->getParts();
        }
    }

    /**
     * Return the base part of the message. This public function does NOT
     * return a reference to make sure that the whole Message
     * object isn't accidentally modified.
     *
     * @return Message  The base Part of the message.
     */
    public function getBasePart()
    {
        $this->buildMessage();
        return $this;
    }

    /**
     * Retrieve a specific MIME part.
     *
     * @param string $id  The Part ID string.
     *
     * @return Part  The Part requested, or false if the part
     *                    doesn't exist.
     */
    public function &getPart($id)
    {
        if ($this->_build) {
            $part = parent::getPart($id);
        } else {
            $this->buildMessage();
            $part = $this->getPart($id);
        }
        if (is_a($part, 'Message')) {
            $newpart = new Part();
            $skip = array('_build', '_defaultServer');
            foreach (array_keys(get_object_vars($part)) as $key) {
                /* Ignore local variables that aren't a part of the original
                 * class. */
                if (!in_array($key, $skip)) {
                    $newpart->$key = &$part->$key;
                }
            }
            return $newpart;
        } else {
            return $part;
        }
    }

}

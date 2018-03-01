<?php

namespace Jorjsmile\AS2;
/**
 * AS2Secure - PHP Lib for AS2 message encoding / decoding
 * 
 * @author  Sebastien MALOT <contact@as2secure.com>
 * 
 * @copyright Copyright (c) 2010, Sebastien MALOT
 * 
 * Last release at : {@link http://www.as2secure.com}
 * 
 * This file is part of AS2Secure Project.
 *
 * AS2Secure is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * AS2Secure is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with AS2Secure.
 * 
 * @license http://www.gnu.org/licenses/lgpl-3.0.html GNU General Public License
 * @version 0.9.0
 * 
 */

/**
 * Class AS2Partner
 * @package Jorjsmile\AS2
 */
class AS2Partner {
    // general information
    public $is_local = false;
    public $name     = '';
    public $id       = '';
    public $email    = '';
    public $comment  = '';
    
    // security
    public $sec_pkcs12               = ''; // must contain private/certificate/ca chain
    public $sec_pkcs12_password      = '';
    
    public $sec_certificate          = ''; // must contain certificate/ca chain
    
    public $sec_signature_algorithm  = self::SIGN_SHA1;
    public $sec_encrypt_algorithm    = self::CRYPT_3DES;
    
    // sending data
    public $send_compress            = false;
    public $send_url                 = ''; // full url including "http://" or "https://"
    public $send_subject             = 'AS2 Message Subject';
    public $send_content_type        = 'application/EDI-Consent';
    public $send_credencial_method   = self::METHOD_NONE;
    public $send_credencial_login    = '';
    public $send_credencial_password = '';
    public $send_encoding            = self::ENCODING_BASE64;
    
    // notification process
    public $mdn_url                  = '';
    public $mdn_subject                = 'AS2 MDN Subject';
    public $mdn_request              = self::ACK_SYNC;
    public $mdn_signed               = true;
    public $mdn_credencial_method    = self::METHOD_NONE;
    public $mdn_credencial_login     = '';
    public $mdn_credencial_password  = '';

    // event trigger connector
    public $connector_class          = 'AS2Connector';
    
    // 
    protected static $stack = array();
    
    // security methods
    const METHOD_NONE   = 'NONE';
    const METHOD_AUTO   = CURLAUTH_ANY;
    const METHOD_BASIC  = CURLAUTH_BASIC;
    const METHOD_DIGECT = CURLAUTH_DIGEST;
    const METHOD_NTLM   = CURLAUTH_NTLM;
    const METHOD_GSS    = CURLAUTH_GSSNEGOTIATE;
    
    // transfert content encoding
    const ENCODING_BASE64 = 'base64';
    const ENCODING_BINARY = 'binary';
    
    // ack methods
    const ACK_SYNC  = 'SYNC';
    const ACK_ASYNC = 'ASYNC';
    
    // 
    const SIGN_NONE = 'none';
    const SIGN_SHA1 = 'sha1';
    const SIGN_MD5  = 'md5';
    
    // http://www.openssl.org/docs/apps/enc.html#SUPPORTED_CIPHERS
    const CRYPT_NONE    = 'none';
    const CRYPT_RC2_40  = 'rc2-40'; // default
    const CRYPT_RC2_64  = 'rc2-64';
    const CRYPT_RC2_128 = 'rc2-128';
    const CRYPT_DES     = 'des';
    const CRYPT_3DES    = 'des3';
    const CRYPT_AES_128 = 'aes128';
    const CRYPT_AES_192 = 'aes192';
    const CRYPT_AES_256 = 'aes256';

    /**
     * Return the list of available signatures
     * 
     * @return array
     */
    public static function getAvailablesSignatures()
    {
        return array('NONE' => self::SIGN_NONE,
                     'SHA1' => self::SIGN_SHA1,
                     );
    }
    
    /**
     * Return the list of available cypher
     * 
     * @return array
     */
    public static function getAvailablesEncryptions()
    {
        return array('NONE'    => self::CRYPT_NONE,
                     'RC2_40'  => self::CRYPT_RC2_40,
                     'RC2_64'  => self::CRYPT_RC2_64,
                     'RC2_128' => self::CRYPT_RC2_128,
                     'DES'     => self::CRYPT_DES,
                     '3DES'    => self::CRYPT_3DES,
                     'AES_128' => self::CRYPT_AES_128,
                     'AES_192' => self::CRYPT_AES_192,
                     'AES_256' => self::CRYPT_AES_256,
                     );
    }

    /**
     * Return an AS2Partner object for a specified Partner ID
     *
     * @param String $partner_id
     * @param array $config
     * @param bool $reload
     * @return object : The partner requested
     * @throws \Exception
     */
    public static function getPartner($partner_id, $config = [], $reload = true)
    {
        if ($partner_id instanceof AS2Partner)
            return $partner_id;
        else if(is_array($partner_id))
            return self::getPartner(...$partner_id);
        
        $partner_id = trim($partner_id, '"');

        if(!isset(self::$stack[$partner_id]) && empty($config))
            throw new \Exception("Config strongly required");

        // get from stack instance
        if (!empty($config)  && ($reload || !isset(self::$stack[$partner_id])) )
            self::$stack[$partner_id] = new self($config);

        return self::$stack[$partner_id];
    }

    /**
     * Restricted constructor
     * 
     * @param array $data       The data to set from
     */
    protected function __construct($data)
    {
        // set properties with data
        foreach($data as $key => $value){
            if (!property_exists($this, $key) || is_null($value))
                continue;

            $this->$key = $value;
        }
    }

    /**
     * Magic method
     * 
     */
    public function __toString(){
        return $this->id;
    }
}

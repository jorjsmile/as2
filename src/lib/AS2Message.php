<?php

namespace Jorjsmile\AS2;

use Jorjsmile\AS2\Helpers\MIME\Part;

class AS2Message extends AS2Abstract {
    
    protected $mic_checksum = false;
    
    public function __construct($data, $params = array()) {
        parent::__construct($data, $params);

        if ($data instanceof AS2Request){
            $this->path = $data->getPath();
        }
        elseif ($data instanceof Part){
            $this->path = AS2Adapter::getTempFilename();
            file_put_contents($this->path, $data->toString(true));
        }
        elseif ($data){
            if (!isset($params['is_file']) || $params['is_file'])
                $this->addFile($data, '', '', true);
            else
                $this->addFile($data, '', '', false);
        }

        if (isset($params['mic'])){
            $this->mic_checksum = $params['mic'];
        }
    }

    /**
     * Add file to the message
     * 
     * @param string  $data        The content or the file
     * @param string  $mimetype    The mimetype of the message
     * @param boolean $is_file     If file
     * @param string  $encoding    The encoding to use for transfert
     * 
     * @return boolean
     */
    public function addFile($data, $mimetype = '', $filename = '', $is_file = true, $encoding = 'base64'){
        if (!$is_file){
            $file    = AS2Adapter::getTempFilename();
            file_put_contents($file, $data);
            $data    = $file;
            $is_file = true;
        }
        else{
            if (!$filename) $filename = basename($data);
        }

        if (!$mimetype) $mimetype = AS2Adapter::detectMimeType($data);

        $this->files[] = array('path'     => $data,
                               'mimetype' => $mimetype,
                               'filename' => $filename,
                               'encoding' => $encoding);
        return true;
    }

    /**
     * Return files which compose the message (should contain at least one file)
     * 
     * @return array
     */
    public function getFiles(){
        return $this->files;
    }
    
    /**
     * Return the last calculated checksum
     * 
     * @return string
     */
    public function getMicChecksum() {
        return $this->mic_checksum;
    }
    
    /**
     * Return the url to send message
     * 
     * @return string
     */
    public function getUrl() {
        return $this->getPartnerTo()->send_url;
    }
    
    /**
     * Return the authentication to use to send message to the partner
     * 
     * @return array
     */
    public function getAuthentication() {
        return array('method'   => $this->getPartnerTo()->send_credencial_method,
                     'login'    => $this->getPartnerTo()->send_credencial_login,
                     'password' => $this->getPartnerTo()->send_credencial_password);
    }
    
    /**
     * Build message and encode it (signing and/or crypting)
     * 
     */
    public function encode() {
        if (!$this->getPartnerFrom() instanceof AS2Partner || !$this->getPartnerTo() instanceof AS2Partner)
            throw new AS2Exception('Object not properly initialized');
        
        // initialisation
        $this->mic_checksum = false;
        $this->setMessageId(self::generateMessageID($this->getPartnerFrom()));

        // chargement et construction du message
        $files = $this->getFiles();
        
        // initial message creation : mime_part
        // TODO : use adapter to build multipart file
        try {
            // managing all files (parts)
            $parts = array();
            foreach($files as $file){
                $mime_part = new Part($file['mimetype']);
                $mime_part->setContents(file_get_contents($file['path']));
                $mime_part->setName($file['filename']);
                if ($file['encoding'])
                    $mime_part->setTransferEncoding($file['encoding']);

                $parts[] = $mime_part;
            }
            if (count($parts) > 1){
                // handling multipart file
                $mime_part = new Part('multipart/mixed');
                foreach($parts as $part)
                    $mime_part->addPart($part);
            }
            else{
                // handling mono part (body)
                $mime_part = $parts[0];
            }
            
            $file = AS2Adapter::getTempFilename();
            file_put_contents($file, $mime_part->toString());
        }
        catch(\Exception $e) {
            throw $e;
        }
        
        // signing file if wanted by Partner_To
        if ($this->getPartnerTo()->sec_signature_algorithm != AS2Partner::SIGN_NONE) {
            try {
                $file = $this->adapter->sign($file, $this->getPartnerTo()->send_compress, $this->getPartnerTo()->send_encoding);
                $this->is_signed = true;
                
                //echo file_get_contents($file);
                $this->mic_checksum = AS2Adapter::getMicChecksum($file);
            }
            catch(\Exception $e) {
                throw $e;
            }
        }

        // crypting file if wanted by Partner_To
        if ($this->getPartnerTo()->sec_encrypt_algorithm != AS2Partner::CRYPT_NONE) {
            try {
                $file = $this->adapter->encrypt($file);
                $this->is_crypted = true;
            }
            catch(\Exception $e) {
                throw $e;
            }
        }

        $this->path = $file;
        /*if ($mime_part->getTransferEncoding() == 'base64'){
            file_put_contents($this->path, base64_decode($mime_part->toString(false)));
        }
        else{
            file_put_contents($this->path, $mime_part->toString());
        }*/

        // headers setup
        $headers = array(
             'AS2-From'                     => '"' . $this->getPartnerFrom()->id . '"',
             'AS2-To'                       => '"' . $this->getPartnerTo()->id . '"',
             'AS2-Version'                  => '1.0',
             'From'                         => $this->getPartnerFrom()->email,
             'Subject'                      => $this->getPartnerFrom()->send_subject,
             'Message-ID'                   => $this->getMessageId(),
             'Mime-Version'                 => '1.0',
             'Disposition-Notification-To'  => $this->getPartnerFrom()->send_url,
             'Recipient-Address'            => $this->getPartnerTo()->send_url,
             'User-Agent'                   => 'AS2Secure - PHP Lib for AS2 message encoding / decoding',
        );
        
        if ($this->getPartnerTo()->mdn_signed) {
            $headers['Disposition-Notification-Options'] = 'signed-receipt-protocol=optional, pkcs7-signature; signed-receipt-micalg=optional, sha1';
        }
        
        if ($this->getPartnerTo()->mdn_request == AS2Partner::ACK_ASYNC) {
            $headers['Receipt-Delivery-Option'] = $this->getPartnerFrom()->send_url;
        }
        
        $this->headers = new AS2Header($headers);

        // look for additionnal headers from message
        // eg : content-type
        $content = file_get_contents($this->path);
        $this->headers->addHeadersFromMessage($content);
        if (strpos($content, "\n\n") !== false) $content = substr($content, strpos($content, "\n\n") + 2);
        file_put_contents($this->path, $content);
        
        return true;
    }
    
    /**
     * Decode message extracting files from message
     * 
     * @return array    List of files extracted
     */
    public function decode() {
        $this->files = $this->adapter->extract($this->getPath());
    }
    
    /**
     * Generate a MDN from the message
     * 
     * @param object  $exception   The exception if error handled
     * 
     * @return object              The MDN generated
     */
    public function generateMDN($exception = null) {
        $mdn = new AS2MDN($this);

        $message_id = $this->getHeader('message-id');
        $partner    = $this->getPartnerTo()->id;
        $mic        = $this->getMicChecksum();

        $mdn->setAttribute('Original-Recipient',  'rfc822; "' . $partner . '"');
        $mdn->setAttribute('Final-Recipient',     'rfc822; "' . $partner . '"');
        $mdn->setAttribute('Original-Message-ID', $message_id);
        if ($mic)
            $mdn->setAttribute('Received-Content-MIC', $mic);

        if (is_null($exception)){
            $mdn->setMessage('The AS2 message has been received.');
            $mdn->setAttribute('Disposition-Type', 'processed');
        }
        else {
            if (!$exception instanceof AS2Exception)
                $exception = new AS2Exception($exception->getMessage());

            $mdn->setMessage($exception->getMessage());
            $mdn->setAttribute('Disposition-Type', 'failure');
            $mdn->setAttribute('Disposition-Modifier', $exception->getLevel() . ': ' . $exception->getMessageShort());
        }

        return $mdn;
    }
}

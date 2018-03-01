<?php

return [
    'is_local' => true,
    'name'     => 'mycompanyAS2',
    'id'       => 'mycompanyAS2',
    'email'    => 'info@mendelson.de',
    'comment'  => '',
    
    // security
    'sec_pkcs12'               => dirname(__FILE__).'/mycompanyAS2/key1.p12',
    'sec_pkcs12_password'      => 'test',
    
    'sec_signature_algorithm'  => \Jorjsmile\AS2\AS2Partner::SIGN_SHA1,
    'sec_encrypt_algorithm'    => \Jorjsmile\AS2\AS2Partner::CRYPT_3DES,

    'send_url'                 => 'http://as2-php7.loc/server.php',

    // notification process
    'mdn_request'              => \Jorjsmile\AS2\AS2Partner::ACK_SYNC,
];

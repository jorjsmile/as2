<?php

return [
    'is_local' => false,
    'name'     => 'mendelsontestAS2',
    'id'       => 'mendelsontestAS2',
    'email'    => 'info@mendelson.de',
    'comment'  => '',
    
    // security
    'sec_pkcs12'               => dirname(__FILE__).'/mendelsontestAS2/key2.p12',
    'sec_pkcs12_password'      => 'test',
    
    'sec_signature_algorithm'  => \Jorjsmile\AS2\AS2Partner::SIGN_SHA1,
    'sec_encrypt_algorithm'    => \Jorjsmile\AS2\AS2Partner::CRYPT_3DES,

    // sending data
    'send_url'                 => 'http://as2.loc/server.php',

    // mdn notification
    'mdn_request'              => \Jorjsmile\AS2\AS2Partner::ACK_SYNC,
];

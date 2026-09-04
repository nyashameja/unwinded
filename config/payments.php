<?php
return [
    'default'  => 'payfast',
    'payfast'  => [
        'merchant_id'  => $_ENV['PAYFAST_MERCHANT_ID']  ?? '',
        'merchant_key' => $_ENV['PAYFAST_MERCHANT_KEY'] ?? '',
        'passphrase'   => $_ENV['PAYFAST_PASSPHRASE']   ?? '',
        'sandbox'      => filter_var($_ENV['PAYFAST_SANDBOX'] ?? true, FILTER_VALIDATE_BOOLEAN),
        'itn_url'      => $_ENV['PAYFAST_ITN_URL']      ?? '',
    ],
    'ticket_hmac_key' => $_ENV['TICKET_HMAC_KEY'] ?? '',
    'gallery_token_key' => $_ENV['GALLERY_TOKEN_KEY'] ?? '',
];

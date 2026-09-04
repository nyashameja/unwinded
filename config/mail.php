<?php
return [
    'host'          => $_ENV['MAIL_HOST']          ?? 'smtp.gmail.com',
    'port'          => (int) ($_ENV['MAIL_PORT']   ?? 587),
    'encryption'    => $_ENV['MAIL_ENCRYPTION']    ?? 'tls',
    'auth_method'   => $_ENV['MAIL_AUTH_METHOD']   ?? 'login',
    'username'      => $_ENV['MAIL_USERNAME']      ?? '',
    'password'      => $_ENV['MAIL_PASSWORD']      ?? '',
    'from_address'  => $_ENV['MAIL_FROM_ADDRESS']  ?? '',
    'from_name'     => $_ENV['MAIL_FROM_NAME']     ?? 'Unwinded',
    'reply_to'      => $_ENV['MAIL_REPLY_TO']      ?? '',
    'oauth_client_id'     => $_ENV['MAIL_OAUTH_CLIENT_ID']     ?? '',
    'oauth_client_secret' => $_ENV['MAIL_OAUTH_CLIENT_SECRET'] ?? '',
    'oauth_refresh_token' => $_ENV['MAIL_OAUTH_REFRESH_TOKEN'] ?? '',
    // development mode: 'live' | 'log' | 'catch_all'
    'mode'          => $_ENV['MAIL_MODE']          ?? 'live',
    'catch_all'     => $_ENV['MAIL_CATCH_ALL']     ?? '',
];

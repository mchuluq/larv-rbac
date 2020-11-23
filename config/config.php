<?php

return array(
    'unauthenticated_redirect_uri' => '/auth/login',
    'authenticated_redirect_uri' => '/home',

    'route' => true,

    'login_max_attempts' => 3, //times
    'login_decay' => 30, // minutes,

    'views' => [
        'login' => 'auth.login',
        'email' => 'auth.passwords.email',
        'reset' => 'auth.passwords.reset',
        'confirm' => 'auth.passwords.confirm',
        'account' => 'auth.account'
    ],
);
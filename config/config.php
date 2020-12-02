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
        'account' => 'auth.account',
        'google2fa_register' => 'auth.google2fa.register',
        'google2fa_confirm' => 'auth.google2fa.confirm',
    ],

    'gravatar_options' => [
        's' => 80,
        'd' => 'retro',
        'r' => 'g',
    ],

    'otp_input_name' => 'otp_input'
);
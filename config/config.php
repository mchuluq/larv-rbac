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
        'otp_confirm' => 'auth.otp.confirm',
    ],

    'gravatar_options' => [
        's' => 80,
        'd' => 'retro',
        'r' => 'g',
    ],

    'otp_input_name' => 'otp_input',
    'otp_timeout' => 10800,
    'otp_failed_response' => 'Kode OTP tidak sesuai',
    'otp_enabled_success' => 'OTP telah diaktifkan',
    'otp_disabled_success' => 'OTP telah dimatikan',
);
<?php

return array(
    'unauthenticated_redirect_uri' => '/login',
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
        'otp_register' => 'auth.otp.register',
        'otp_confirm' => 'auth.otp.confirm',
    ],

    'gravatar_options' => [
        's' => 80,
        'd' => 'retro',
        'r' => 'g',
    ],

    'otp_input_name' => 'otp_input',
    'otp_timeout' => 300,
    'otp_failed_response' => 'Kode OTP tidak sesuai',
    'otp_enabled_success' => 'OTP telah diaktifkan',
    'otp_disabled_success' => 'OTP telah dimatikan',

    'otp_session_identifier' => 'otp_session',
    'otp_confirm_identifier' => 'otp_confirmed',
);
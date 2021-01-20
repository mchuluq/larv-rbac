<?php

return array(
    'unauthenticated_redirect_uri' => '/login',
    'authenticated_redirect_uri' => '/home',

    'route' => true,

    'views' => [
        'account' => 'vendor.rbac.account',
        'otp_register' => 'vendor.rbac.otp-register',
        'otp_confirm' => 'vendor.rbac.otp-confirm',
    ],

    'otp_input_name' => 'otp_input',
    'otp_session_identifier' => 'otp_session'
);
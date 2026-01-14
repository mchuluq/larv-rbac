<?php

return array(
    'unauthenticated_redirect_uri' => '/login',
    'authenticated_redirect_uri' => '/home',

    'route' => true,

    'views' => [
        'account' => 'vendor.rbac.account',
    ],

    'access_type' => ['prodi','fakultas','level','organisasi'],

    'account_types' => [
        // model name => label
    ],

    'max_devices' => 5,
    'enforce_limit' => true,
);
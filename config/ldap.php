<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP Authentication
    |--------------------------------------------------------------------------
    |
    | When 'enabled' is true (LDAP_ENABLED=true in .env), the login flow will
    | authenticate against your company's LDAP server before falling back to
    | local database credentials.
    |
    */

    'enabled' => env('LDAP_ENABLED', false),

    'host' => env('LDAP_HOST', ''),

    'port' => (int) env('LDAP_PORT', 389),

    'base_dn' => env('LDAP_BASE_DN', ''),

];

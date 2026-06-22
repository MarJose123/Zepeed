<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Admin Account
    |--------------------------------------------------------------------------
    |
    | These values are used by the `app:create-user-account --default` command
    | to seed the first administrator account on initial container boot.
    | The command is idempotent — if the email already exists it exits cleanly.
    |
    | Override any of these via environment variables before first boot.
    | After first login, update credentials through the Profile Settings UI.
    |
    */

    'default_admin' => [
        'name'     => env('DEFAULT_ADMIN_NAME', 'Zepeed Admin'),
        'email'    => env('DEFAULT_ADMIN_EMAIL', 'admin@zepeed.local'),
        'password' => env('DEFAULT_ADMIN_PASSWORD', 'zepeed_admin'),
    ],

];

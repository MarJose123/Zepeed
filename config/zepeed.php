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

    /*
    |--------------------------------------------------------------------------
    | GitHub Repository
    |--------------------------------------------------------------------------
    |
    | The URL of the project's GitHub repository. Used by the "star the
    | project" prompts — the web star dialog shown to authenticated users
    | and the post-success prompt of interactive Artisan commands.
    |
    | Set GITHUB_REPOSITORY_URL to enable the prompts. When left unset the
    | feature stays disabled: the web dialog is never shown and the Artisan
    | prompt is skipped, so no broken links can be presented.
    |
    */

    'github_repository_url' => env('GITHUB_REPOSITORY_URL'),

];

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
        // No default password fallback — require DEFAULT_ADMIN_PASSWORD to be set.
        // The create-user-account --default command validates the password is present.
        'password' => (string) env('DEFAULT_ADMIN_PASSWORD', ''),
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

    /*
    |--------------------------------------------------------------------------
    | GitHub Star Prompt
    |--------------------------------------------------------------------------
    |
    | Control whether the "star on GitHub" prompt is shown in the web UI.
    | When set to false, the prompt will never appear, even if a repository
    | URL is configured.
    |
    | Set GITHUB_STAR_PROMPT_ENABLED to false to disable the prompt.
    |
    */

    'github_star_prompt_enabled' => env('GITHUB_STAR_PROMPT_ENABLED', true),

];

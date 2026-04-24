<?php

return [

    /*
    |--------------------------------------------------------------------------
    | VCS Provider
    |--------------------------------------------------------------------------
    |
    | Determines the source for repositories in the packaging wizard.
    | Supported: "github", "gitlab"
    |
    | - "github" — repositories are read from config/github-repos.php
    | - "gitlab" — repositories are fetched dynamically from the logged-in
    |               user's GitLab account via OAuth
    |
    */

    'vcs_provider' => env('VCS_PROVIDER', 'github'),

];

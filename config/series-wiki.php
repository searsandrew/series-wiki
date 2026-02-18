<?php

return [
    /*
     |-----------------------------------------------------------------------
     | User model (optional)
     |-----------------------------------------------------------------------
     | If your host app uses a custom user model, you can set it here.
     */
    'user_model' => env('SERIES_WIKI_USER_MODEL', \App\Models\User::class),

    /*
     |-----------------------------------------------------------------------
     | Spoiler / gate behavior
     |-----------------------------------------------------------------------
     */
    'spoilers' => [
        // Default behavior when a gated block is locked. Options: 'safe', 'stub'
        'default_locked_mode' => 'safe',

        // Text used for stub blocks (when locked_mode = 'stub').
        'stub_text' => 'Spoiler content hidden. Continue reading to unlock this section.',
    ],
];
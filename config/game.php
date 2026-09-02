<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Game Timer Duration
    |--------------------------------------------------------------------------
    |
    | The default time limit in seconds allocated for each question.
    | Can be overridden on a per-session basis or through the environment.
    |
    */
    'timer_duration_seconds' => (int) env('GAME_TIMER_DURATION', 30),

    /*
    |--------------------------------------------------------------------------
    | Moderator Secret Key
    |--------------------------------------------------------------------------
    |
    | Secret key passed in the X-Moderator-Key header or Authorization header
    | to authorize moderator score changes and game administrative actions.
    |
    */
    'moderator_secret' => env('MODERATOR_SECRET_KEY', 'moderator-secret-key-12345'),

    /*
    |--------------------------------------------------------------------------
    | Default Sections Count
    |--------------------------------------------------------------------------
    |
    | Standard number of sections in a full quiz game.
    |
    */
    'default_sections_count' => 4,
];

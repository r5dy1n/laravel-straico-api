<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Straico API Key
    |--------------------------------------------------------------------------
    |
    | Your Straico API key. You can obtain this from your Straico account.
    | It's recommended to store this in your .env file for security.
    |
    */
    'api_key' => env('STRAICO_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Straico Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the Straico API endpoints.
    | The default value points to the production environment.
    |
    */
    'base_url' => env('STRAICO_BASE_URL', 'https://api.straico.com/v1'), // Default to v1 API

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    |
    | The maximum number of seconds to wait for a response from the API.
    |
    */
    'timeout' => env('STRAICO_TIMEOUT', 30),
];
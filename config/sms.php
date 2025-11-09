<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Provider
    |--------------------------------------------------------------------------
    |
    | Supported: "twilio", "africas_talking", "log"
    |
    */

    'default_provider' => env('SMS_PROVIDER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | SMS Provider Configurations
    |--------------------------------------------------------------------------
    */

    'providers' => [
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_FROM_NUMBER'),
        ],

        'africas_talking' => [
            'username' => env('AFRICAS_TALKING_USERNAME'),
            'api_key' => env('AFRICAS_TALKING_API_KEY'),
            'from' => env('AFRICAS_TALKING_FROM', ''),
        ],

        'log' => [
            // No configuration needed - just logs messages
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Country Code
    |--------------------------------------------------------------------------
    |
    | Used for normalizing phone numbers (Zimbabwe = 263)
    |
    */

    'default_country_code' => env('SMS_DEFAULT_COUNTRY_CODE', '263'),
];

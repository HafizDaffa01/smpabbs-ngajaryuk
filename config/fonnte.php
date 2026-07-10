<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Fonnte WhatsApp API Configuration
    |--------------------------------------------------------------------------
    |
    | Used for sending WhatsApp attendance notifications and reminders.
    | Get your API key and device ID at https://fonnte.com
    |
    */

    'api_key' => env('FONNTE_API_KEY', ''),

    'device_id' => env('FONNTE_DEVICE_ID', ''),

    'api_url' => 'https://api.fonnte.com/send',

    'country_code' => '62',

];

<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    */

    'topup_mart' => [
        'base_url' => env('TOPUPMART_BASE_URL', 'https://topupmart.online/api/v2'),
        'api_key'  => env('TOPUPMART_API_KEY', ''),
    ],

    'happy_recharge_center' => [
        'base_url' => env('HRC_BASE_URL', 'http://happyrechargecenter.com/RechargeApi'),
        'api_key'  => env('HRC_API_KEY', '334d7b447e9459fcbafe9441a'),
    ],
];

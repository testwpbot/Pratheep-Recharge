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

    'tmobiling' => [
        'base_url' => env('TMOBILING_BASE_URL', 'https://www.tmobiling.lk/livenew/apis/api_request'),
        'api_key'  => env('TMOBILING_API_KEY', ''),
    ],

    'happy_recharge_center' => [
        'base_url'    => env('HRC_BASE_URL', 'http://happyrechargecenter.com/RechargeApi'),
        'api_key'     => env('HRC_API_KEY', ''),
        'cancel_path' => env('HRC_CANCEL_PATH', ''),
    ],
];

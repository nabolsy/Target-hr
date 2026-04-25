<?php

return [
    'api_key' => env('MOYASAR_API_KEY'),
    'publishable_key' => env('MOYASAR_PUBLISHABLE_KEY'),
    'callback_url' => env('MOYASAR_CALLBACK_URL', 'http://localhost:3000/payment/callback'),
    'base_url' => 'https://api.moyasar.com/v1',
];

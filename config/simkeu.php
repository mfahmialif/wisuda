<?php

return [
    'simkeu_api_key' => env('SIMKEU_API_KEY'),
    'simkeu_url' => env('SIMKEU_URL'),
    'simkeu_pasca_api_key' => env('SIMKEU_PASCA_API_KEY'),
    'simkeu_pasca_url' => env('SIMKEU_PASCA_URL'),

    // SIMKEU V2 - untuk sinkronisasi pembayaran wisuda
    'v2_base_url' => env('SIMKEUV2_BASE_URL', ''),
    'v2_api_key'  => env('SIMKEUV2_API_KEY', ''),
    'v2_timeout'  => env('SIMKEUV2_TIMEOUT', 30),
];
<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Local / LAN development host
    |--------------------------------------------------------------------------
    |
    | Set DEV_LAN_HOST in .env to your machine's LAN IP when testing from a
    | phone or another device on the same network (e.g. 192.168.1.42).
    |
    */

    'lan_host' => env('DEV_LAN_HOST'),

    'lan_frontend_port' => env('DEV_LAN_FRONTEND_PORT', '5173'),

    'lan_api_port' => env('DEV_LAN_API_PORT', '8000'),

];

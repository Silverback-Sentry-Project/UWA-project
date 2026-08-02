<?php

if (! function_exists('wildwatch_lan_host_domains')) {
    /**
     * @return list<string>
     */
    function wildwatch_lan_host_domains(): array
    {
        $lanHost = env('DEV_LAN_HOST');
        if (! is_string($lanHost) || $lanHost === '') {
            return [];
        }

        $frontendPort = env('DEV_LAN_FRONTEND_PORT', '5173');
        $apiPort = env('DEV_LAN_API_PORT', '8000');

        return array_values(array_unique([
            $lanHost,
            "{$lanHost}:{$frontendPort}",
            "{$lanHost}:{$apiPort}",
        ]));
    }
}

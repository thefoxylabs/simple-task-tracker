<?php

use App\Support\Settings;

if (! function_exists('settings')) {
    /**
     * Read a company setting, or get the settings service.
     */
    function settings(?string $key = null, mixed $default = null): mixed
    {
        $settings = app(Settings::class);

        return $key === null ? $settings : $settings->get($key, $default);
    }
}

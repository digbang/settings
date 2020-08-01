<?php

if (! function_exists('setting')) {

    /**
     * @param string $key
     * @return mixed|null
     */
    function setting(string $key)
    {
        /** @var \Digbang\Settings\Services\Settings $service */
        $service = app(\Digbang\Settings\Services\Settings::class);

        return $service->getValue($key);
    }
}

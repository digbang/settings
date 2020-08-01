<?php

namespace Digbang\Settings\Repositories;

use Digbang\Settings\Entities\Setting;

interface CacheRepository
{
    /**
     * @param string $key
     * @return mixed|null
     */
    public function getValue(string $key);

    public function put(Setting $setting): void;

    public function forget(Setting $setting): void;
}

<?php

namespace Digbang\Settings\Repositories;

use DateInterval;
use Digbang\Settings\Entities\Setting;
use Illuminate\Cache\Repository;

class CacheSettingsRepository implements CacheRepository
{
    /** @var Repository */
    private $cache;

    public function __construct(Repository $cache)
    {
        $this->cache = $cache;
    }

    /** @inheritDoc */
    public function getValue(string $key)
    {
        return $this->cache->get($key, null);
    }

    public function has(string $key): bool
    {
        return $this->cache->has($key);
    }

    public function put(Setting $setting): void
    {
        $this->cache->put($setting->getKey(), $setting->getValue(), new DateInterval('P1Y'));
    }

    public function forget(Setting $setting): void
    {
        $this->cache->forget($setting->getKey());
    }
}

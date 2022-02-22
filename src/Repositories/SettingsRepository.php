<?php

namespace Digbang\Settings\Repositories;

use Digbang\Settings\Entities\Setting;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface SettingsRepository
{
    /**
     * Get a setting by key.
     */
    public function get(string $key): Setting;

    /**
     * List settings by keys. (Exact matching).
     *
     * @return Setting[]
     */
    public function list(array $keys);

    /**
     * Gets the settings value directly.
     *
     * @return mixed
     */
    public function getValue(string $key);

    /**
     * Get all settings.
     *
     * @return Setting[]|Collection
     */
    public function all(): Collection;

    /**
     * Set a setting's value directly.
     *
     * @param mixed $value
     */
    public function setValue(string $key, $value): Setting;

    /**
     * Search for settings by key, name or description.
     *
     * @return Setting[]|LengthAwarePaginator
     */
    public function search(array $filters, array $orderBy, ?int $perPage = null, int $page = 1);
}

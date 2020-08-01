<?php

namespace Digbang\Settings;

use Digbang\Settings\Entities\Setting;
use Illuminate\Support\Collection;

class SettingsDTO
{
    /** @var Collection */
    private $newSettings;
    /** @var Collection */
    private $deletedSettings;

    public function __construct()
    {
        $this->newSettings = collect();
        $this->deletedSettings = collect();
    }

    public function addToCreate(Setting $setting): void
    {
        $this->newSettings->add($setting);
    }

    public function addToDelete(Setting $setting): void
    {
        $this->deletedSettings->add($setting);
    }

    public function toCreate(): Collection
    {
        return $this->newSettings;
    }

    public function toDelete(): Collection
    {
        return $this->deletedSettings;
    }
}

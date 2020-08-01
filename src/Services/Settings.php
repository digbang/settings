<?php

namespace Digbang\Settings\Services;

use Digbang\Settings\Entities\Setting;
use Digbang\Settings\Repositories\CacheRepository;
use Digbang\Settings\Repositories\SettingsRepository;
use Digbang\Settings\SettingsDTO;
use Doctrine\ORM\EntityManager;
use Illuminate\Support\Collection;

class Settings
{
    /** @var CacheRepository */
    private $cacheRepository;
    /** @var SettingsRepository */
    private $repository;
    /** @var EntityManager */
    private $em;

    public function __construct(CacheRepository $cacheRepository, SettingsRepository $repository, EntityManager $em)
    {
        $this->cacheRepository = $cacheRepository;
        $this->repository = $repository;
        $this->em = $em;
    }

    /**
     * @param string $key
     * @return mixed|null
     */
    public function getValue(string $key)
    {
        if ($this->cacheRepository->has($key)) {
            return $this->cacheRepository->getValue($key);
        }

        $setting = $this->repository->get($key);

        $this->cacheRepository->put($setting);

        return $setting->getValue();
    }

    public function save(SettingsDTO $settings): void
    {
        $this->create($settings->toCreate());

        $this->remove($settings->toDelete());
    }

    public function update(Setting $setting, $newValue): void
    {
        $this->cacheRepository->forget($setting);

        $this->repository->setValue($setting->getKey(), $newValue);

        $this->cacheRepository->put($setting);
    }

    private function create(Collection $settings): void
    {
        if ($settings->isEmpty()) {
            return;
        }

        $settings->each(function (Setting $setting) {
            $this->em->persist($setting);
        });

        $this->em->flush();

        $settings->each(function (Setting $setting) {
            $this->cacheRepository->put($setting);
        });
    }

    private function remove(Collection $settings): void
    {
        if ($settings->isEmpty()) {
            return;
        }

        $settings->each(function (Setting $setting) {
            $this->cacheRepository->forget($setting);
        });

        $settings->each(function (Setting $setting) {
            $this->em->remove($setting);
        });

        $this->em->flush();
    }
}

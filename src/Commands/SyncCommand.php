<?php

declare(strict_types = 1);

namespace Digbang\Settings\Commands;

use Digbang\Settings\Entities\EnumSetting;
use Digbang\Settings\Entities\Setting;
use Digbang\Settings\Repositories\SettingsRepository;
use Digbang\Utils\Enumerables\Enum;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class SyncCommand extends Command
{
    private const NAME = 'name';
    private const DESCRIPTION = 'description';
    private const NULLABLE = 'nullable';
    private const TYPE = 'type';
    private const DEFAULT = 'default';

    protected $signature = 'settings:sync 
        {--dry-run : Only show what would be done, without doing it. }
        {--update-descriptors : Will update name and description fields from the config file. }';

    protected $description = 'Sync configured settings with the database.';

    public function handle(Repository $config, SettingsRepository $settingsRepository, EntityManagerInterface $entityManager)
    {
        $exitStatus = 0;

        $existing = $settingsRepository->all();
        $configured = new Collection($config->get('settings.settings'));

        /** @var Collection $missing */
        $missing = $configured->diffKeys($existing);

        /** @var Collection|Setting[] $removed */
        $removed = $existing->diffKeys($configured);

        foreach ($missing as $key => $setting) {
            try {
                $this->validConfig($setting);

                /** @var Setting $current */
                $current = new $setting[self::TYPE](
                    $key,
                    $setting[self::NAME],
                    Arr::get($setting, self::DESCRIPTION, ''),
                    $this->buildDefault(Arr::get($setting, self::TYPE), Arr::get($setting, self::DEFAULT)),
                    Arr::get($setting, self::NULLABLE, false)
                );

                $this->info("Added [$key].");
                $this->info(print_r($current, true), 'vvv');

                if (! $this->option('dry-run')) {
                    $entityManager->persist($current);
                }
            } catch (\InvalidArgumentException $exception) {
                $this->error("Invalid configuration for setting [$key].");
                $this->error($exception->getMessage(), 'v');

                $exitStatus = 1;
            }
        }

        foreach ($removed as $key => $setting) {
            $this->warn("Removed [$key].");
            $this->warn(print_r($setting, true), 'vvv');

            if (! $this->option('dry-run')) {
                $entityManager->remove($setting);
            }

            $existing->forget($key);
        }

        if ($this->option('update-descriptors')) {
            /** @var Setting $setting */
            foreach ($existing as $setting) {
                try {
                    $key = $setting->getKey();
                    $configuredSetting = $configured[$key];

                    $this->validConfig($configuredSetting);

                    if (! $this->option('dry-run')) {
                        $setting->setName($configuredSetting[self::NAME]);
                        $setting->setDescription($configuredSetting[self::DESCRIPTION]);
                    }

                    $this->info("Updated [$key] descriptors.");
                } catch (\InvalidArgumentException $exception) {
                    $this->error("Invalid configuration for setting [$key].");
                    $this->error($exception->getMessage(), 'v');

                    $exitStatus = 1;
                }
            }
        }

        if (! $this->option('dry-run')) {
            $entityManager->flush();
        }

        return $exitStatus;
    }

    private function validConfig($setting)
    {
        $errors = [];

        if (! array_key_exists(self::TYPE, $setting)) {
            $errors[] = ' - Missing key: [type].';
        } elseif (! class_exists($setting[self::TYPE])) {
            $errors[] = sprintf(' - Class [%s] does not exist.', $setting[self::TYPE]);
        } elseif (! is_subclass_of($setting[self::TYPE], Setting::class)) {
            $errors[] = sprintf(' - Class [%s] must extend [%s].', $setting[self::TYPE], Setting::class);
        }

        if (! array_key_exists(self::NAME, $setting)) {
            $errors[] = ' - Missing key: [name].';
        }

        if (! array_key_exists(self::DEFAULT, $setting) && ! Arr::get($setting, self::NULLABLE)) {
            $errors[] = ' - Cannot create a not-null setting without default value.';
        }

        if (! empty($errors)) {
            throw new \InvalidArgumentException(implode(PHP_EOL, $errors));
        }
    }

    private function buildDefault(string $type, $default)
    {
        if ($type === EnumSetting::class) {
            if ($default !== null && ! is_array($default)) {
                throw new \InvalidArgumentException('Enum setting default value must be an array [classname, value] or null');
            }

            /** @var Enum $classname */
            [$classname, $value] = $default;
            $default = $classname::fromString((string) $value);
        }

        return $default;
    }
}

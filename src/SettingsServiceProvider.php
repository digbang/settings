<?php

namespace Digbang\Settings;

use Digbang\Settings\Repositories\CacheRepository;
use Digbang\Settings\Repositories\CacheSettingsRepository;
use Digbang\Settings\Repositories\DoctrineSettingsRepository;
use Digbang\Settings\Repositories\SettingsRepository;
use Doctrine\ORM\EntityManagerInterface;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\Compilers\BladeCompiler;
use LaravelDoctrine\Fluent\FluentDriver;
use LaravelDoctrine\ORM\Configuration\MetaData\MetaDataManager;
use LaravelDoctrine\ORM\Extensions\MappingDriverChain;

class SettingsServiceProvider extends ServiceProvider
{
    private const PACKAGE = 'settings';

    public function boot(EntityManagerInterface $entityManager, MetaDataManager $metadata, BladeCompiler $blade)
    {
        $this->doctrineMappings($entityManager, $metadata);
        $this->resources();

        $blade->directive('setting', function ($key) {
            $class = SettingsRepository::class;

            return "<?php echo app('$class')->getValue($key); ?>";
        });
    }

    public function register()
    {
        $this->app->bind(CacheRepository::class, CacheSettingsRepository::class);
        $this->app->bind(SettingsRepository::class, DoctrineSettingsRepository::class);

        $this->commands([
            Commands\SyncCommand::class,
        ]);

        $this->mergeConfigFrom($this->configPath(), static::PACKAGE);
    }

    protected function doctrineMappings(EntityManagerInterface $entityManager, MetaDataManager $metadata): void
    {
        /** @var FluentDriver $fluentDriver */
        $fluentDriver = $metadata->driver('fluent', ['mappings' => [
            Mappings\SettingMapping::class,
            Mappings\ArraySettingMapping::class,
            Mappings\BooleanSettingMapping::class,
            Mappings\DateSettingMapping::class,
            Mappings\DateTimeSettingMapping::class,
            Mappings\EmailSettingMapping::class,
            Mappings\EnumSettingMapping::class,
            Mappings\FloatSettingMapping::class,
            Mappings\IntSettingMapping::class,
            Mappings\StringSettingMapping::class,
            Mappings\TimeSettingMapping::class,
            Mappings\UrlSettingMapping::class,
        ]]);

        /** @var MappingDriverChain $chain */
        $chain = $entityManager->getConfiguration()->getMetadataDriverImpl();
        $chain->addDriver($fluentDriver, 'Digbang\Settings');
    }

    protected function resources(): void
    {
        $path = dirname(__DIR__) . '/resources';

        $this->loadTranslationsFrom("$path/lang", static::PACKAGE);
        $this->loadViewsFrom("$path/views", static::PACKAGE);

        $this->publishes([
            "$path/views" => resource_path('views/vendor/' . static::PACKAGE),
        ], 'views');

        $this->publishes([
            "$path/lang" => resource_path('lang/vendor/' . static::PACKAGE),
        ], 'lang');

        $this->publishes([
            $this->configPath() => config_path(static::PACKAGE . '.php'),
        ], 'config');
    }

    private function configPath(): string
    {
        return dirname(__DIR__) . '/config/settings.php';
    }
}

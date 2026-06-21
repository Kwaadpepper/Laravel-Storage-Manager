<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Provider;

use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Application;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\LaravelStorageManager\Console\Commands\CleanOrphanedUploads;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileManager;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventDispatcher;

class StorageManagerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->loadTranslations();
        $this->loadConfig();
        $this->loadViews();
        $this->loadRoutes();
        $this->publishAssets();

        Route::bind('uploadId', function (string $value) {
            try {
                return new UploadId($value);
            } catch (IllegalDomainStateException $e) {
                abort(400, 'Invalid Upload ID format.');
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanOrphanedUploads::class,
            ]);

            $this->app->booted(function () {
                $schedule = $this->app->make(Schedule::class);
                $schedule->command('sm:clean-orphaned-uploads')->daily();
            });
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->registerTranslations();
        $this->registerConfig();
        $this->registerAliases();
        $this->registerDependenciesInjection();
        $this->registerViewSharedData();
    }

    private function registerDependenciesInjection(): void
    {
        $this->app->singleton(
            ConfigRepository::class,
            fn () => new ConfigRepository(
                Arr::wrap(Config::get('storage-manager')),
                Arr::wrap(Config::get('storage-manager::static-config', []))
            )
        );
        $this->app->singleton(
            FileManager::class,
            function (Application $app) {
                $configRepository      = $app->make(ConfigRepository::class);
                $pathNormalizer        = $app->make(PathNormalizer::class);
                $filePropertyExtractor = $app->make(FilePropertyExtractor::class);

                return new FileManager(
                    $pathNormalizer,
                    $filePropertyExtractor,
                    $configRepository->getDefaultDisk()
                );
            }
        );
        $this->app->singleton(
            EventDispatcher::class,
            fn () => new EventDispatcher()
        );
    }

    public function registerAliases(): void
    {
        $this->app->alias(ConfigRepository::class, 'storage-manager.config');
    }

    private function loadConfig(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/storage-manager.php',
            'storage-manager'
        );

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/static-config.php',
            'storage-manager::static-config'
        );
    }

    private function registerConfig(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../config/storage-manager.php' => $this->app->configPath('storage-manager.php'),
            ],
            'storage-manager:config'
        );
    }

    private function loadTranslations(): void
    {
        $this->loadTranslationsFrom(
            __DIR__ . '/../../../resources/lang',
            'storage-manager'
        );
    }

    private function registerTranslations(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../../resources/lang' => $this->app->langPath('vendor/storage-manager'),
            ],
            'storage-manager:translations'
        );
    }

    private function loadRoutes(): void
    {
        $this->loadRoutesFrom(
            __DIR__ . '/../../routes.php'
        );
    }

    private function loadViews(): void
    {
        $this->loadViewsFrom(
            __DIR__ . '/../../../resources/view',
            'storage-manager'
        );
    }

    public function registerViewSharedData(): void
    {

        view()->composer(
            'storage-manager::*',
            function ($view) {
                $configRepository = $this->app->make(ConfigRepository::class);
                $composerJsonPath = __DIR__ . '/../../../composer.json';
                $composer         = Arr::wrap(Cache::remember(
                    'storage-manager-composer',
                    now()->addDay(),
                    fn () => json_decode(
                        file_get_contents($composerJsonPath) ?: '{}',
                        true
                    )
                ));
                $packageName         = strval($configRepository->getStaticConfig('packageName'));
                $composerPackageName = Arr::get($composer, 'name', $packageName);
                $composerPackageName = is_string($composerPackageName) ? $composerPackageName : $packageName;
                $version             = InstalledVersions::getVersion($composerPackageName);

                $packageLogo = route('storage-manager.assets', ['dir' => 'images', 'file' => 'favicon.svg']);

                $view->with('storageManagerConfig', [
                    'packageName'         => $packageName,
                    'packageVersion'      => $version,
                    'packageLogo'         => $packageLogo,
                    'composerPackageName' => $composerPackageName,
                    'appDescription'      => Arr::get($composer, 'description'),
                    'appAuthors'          => Arr::get($composer, 'authors'),
                    'disks'               => Arr::map(
                        $configRepository->getDisksMap(),
                        fn (Disk $_, string $diskName) => $diskName
                    ),
                    'readOnlyDisks'       => array_keys(array_filter(
                        $configRepository->getDisksMap(),
                        fn (Disk $disk) => $disk->readOnly
                    )),
                    'routes'         => [
                        'fmInit'            => route('storage-manager.api.fm.init'),
                        'fmTree'            => route('storage-manager.api.fm.tree'),
                        'fmContent'         => route('storage-manager.api.fm.content'),
                        'fmProperties'      => route('storage-manager.api.fm.properties'),
                        'fmCreateDirectory' => route('storage-manager.api.fm.create-directory'),
                        'fmCreateFile'      => route('storage-manager.api.fm.create-file'),
                        'fmDelete'          => route('storage-manager.api.fm.delete'),
                        'fmRename'          => route('storage-manager.api.fm.rename'),
                        'fmUploadInit'      => route('storage-manager.api.fm.upload.init'),
                        'fmUploadChunk'     => route('storage-manager.api.fm.upload.chunk'),
                        'fmUploadComplete'  => route('storage-manager.api.fm.upload.complete'),
                        'fmUploadStatus'    => route('storage-manager.api.fm.upload.status', [
                            'uploadId' => '__UPLOAD_ID__',
                        ]),

                        'disksList'         => route('storage-manager.api.disks.list'),
                        'disksSelect'       => route('storage-manager.api.disks.select'),
                    ],
                ]);
            }
        );
    }

    private function publishAssets(): void
    {
        $this->publishes(
            [
                __DIR__ . '/../../../resources/js'  => public_path('vendor/storage-manager/js'),
                __DIR__ . '/../../../resources/css' => public_path('vendor/storage-manager/css'),
            ],
            'storage-manager:assets'
        );
    }
}

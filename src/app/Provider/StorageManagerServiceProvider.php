<?php

declare(strict_types=1);

namespace Kwaadpepper\LaravelStorageManager\Provider;

use Composer\InstalledVersions;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Filesystem\Factory as FilesystemFactory;
use Illuminate\Contracts\View\View;
use Illuminate\Filesystem\FilesystemManager;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Kwaadpepper\LaravelStorageManager\Acl\AclManager;
use Kwaadpepper\LaravelStorageManager\ActivityLog\ActivityLogger;
use Kwaadpepper\LaravelStorageManager\Config\StorageManagerConfig;
use Kwaadpepper\LaravelStorageManager\Console\Commands\CalculateDiskUsage;
use Kwaadpepper\LaravelStorageManager\Console\Commands\CleanOrphanedUploads;
use Kwaadpepper\LaravelStorageManager\Console\Commands\CleanTrash;
use Kwaadpepper\LaravelStorageManager\Contract\Executor;
use Kwaadpepper\LaravelStorageManager\Exception\IllegalDomainStateException;
use Kwaadpepper\LaravelStorageManager\Factory\StorageManagerConfigFactory;
use Kwaadpepper\LaravelStorageManager\Lib\Event\EventDispatcher as LSMEventDispatcher;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileMetadataExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FilePropertyExtractor;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileSearchService;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\FileTransferService;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathConflictResolver;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathNormalizer;
use Kwaadpepper\LaravelStorageManager\Lib\FileManager\PathSanitizer;
use Kwaadpepper\LaravelStorageManager\Lib\Security\MimeTypeChecker;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\LocalStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\RemoteStorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\Storage\S3StorageStrategy;
use Kwaadpepper\LaravelStorageManager\Lib\StorageGateway;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadPathResolver;
use Kwaadpepper\LaravelStorageManager\Lib\Upload\UploadSessionService;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Disk;
use Kwaadpepper\LaravelStorageManager\Lib\ValueObjects\Upload\UploadId;
use Kwaadpepper\LaravelStorageManager\Service\ActionExecutorService;
use Kwaadpepper\LaravelStorageManager\Service\ArchiveService;
use Kwaadpepper\LaravelStorageManager\Service\AuthService;
use Kwaadpepper\LaravelStorageManager\Service\DirectoryListingService;
use Kwaadpepper\LaravelStorageManager\Service\DiskService;
use Kwaadpepper\LaravelStorageManager\Service\ThumbnailService;
use Kwaadpepper\LaravelStorageManager\Service\TrashService;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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

        $this->app->make(AclManager::class)->registerDefaultGates($this->app->make(Gate::class));

        Route::bind('uploadId', function (string $value): UploadId {
            try {
                return new UploadId($value);
            } catch (IllegalDomainStateException) {
                throw new HttpException(400, 'Invalid Upload ID format.');
            }
        });

        Route::bind('disk', function (string $value): Disk {
            try {
                return resolve(DiskService::class)->getDisk($value);
            } catch (\InvalidArgumentException) {
                throw new NotFoundHttpException("Disk '{$value}' not found.");
            }
        });

        if ($this->app->runningInConsole()) {
            $this->commands([
                CleanOrphanedUploads::class,
                CleanTrash::class,
                CalculateDiskUsage::class,
            ]);

            $this->app->booted(function (): void {
                $schedule             = $this->app->make(Schedule::class);
                $storageManagerConfig = $this->app->make(StorageManagerConfig::class);

                $schedule->command('sm:clean-orphaned-uploads')->daily();
                $schedule->command('sm:clean-trash')->daily();

                if ($storageManagerConfig->stats->enabled && $storageManagerConfig->stats->scheduleUsageCalculation) {
                    $schedule->command('sm:calculate-disk-usage')->daily();
                }
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
        $this->registerScopedDisks();
        $this->registerAliases();
        $this->registerDependenciesInjection();
        $this->registerViewSharedData();
    }

    private function registerScopedDisks(): void
    {
        $rawConfigs = Arr::wrap(Config::get('storage-manager.disks.configs', []));
        if (empty($rawConfigs)) {
            return;
        }

        $filesystemManager = $this->app->bound('filesystem') ? $this->app->make('filesystem') : null;

        foreach ($rawConfigs as $diskName => $opts) {
            if (! is_string($diskName) || ! is_array($opts)) {
                continue;
            }

            $root = Arr::get($opts, 'root');
            if (! is_string($root) || mb_trim($root) === '' || mb_trim($root) === '/') {
                continue;
            }

            $prefix       = ltrim(mb_trim($root), '/');
            $unscopedName = '__sm_unscoped_' . $diskName;
            $origFsDisk   = Config::get("filesystems.disks.{$diskName}");

            if (is_array($origFsDisk) && (! isset($origFsDisk['driver']) || $origFsDisk['driver'] !== 'scoped')) {
                Config::set("filesystems.disks.{$unscopedName}", $origFsDisk);
                Config::set("filesystems.disks.{$diskName}", [
                    'driver' => 'scoped',
                    'disk'   => $unscopedName,
                    'prefix' => $prefix,
                ]);

                if ($filesystemManager instanceof FilesystemManager) {
                    $filesystemManager->purge($diskName);
                }
            }
        }
    }

    private function registerStorageManagerConfigSingleton(): void
    {
        $this->app->singleton(
            StorageManagerConfig::class,
            function (Application $app): StorageManagerConfig {
                /** @var array<string, mixed> $config */
                $config = Arr::wrap(Config::get('storage-manager'));
                /** @var array<string, mixed> $staticConfig */
                $staticConfig = Arr::wrap(Config::get('storage-manager::static-config', []));
                $rawDisks     = Arr::wrap(Config::get('filesystems.disks', []));
                /** @var array<string, array<string, scalar|null>> $filesystemsDisks */
                $filesystemsDisks = [];
                foreach ($rawDisks as $name => $diskConfig) {
                    if (is_string($name) && is_array($diskConfig)) {
                        /** @var array<string, scalar|null> $typedDiskConfig */
                        $typedDiskConfig = [];
                        foreach ($diskConfig as $k => $v) {
                            if (is_string($k) && (is_scalar($v) || $v === null)) {
                                $typedDiskConfig[$k] = $v;
                            }
                        }
                        $filesystemsDisks[$name] = $typedDiskConfig;
                    }
                }

                return $app->make(StorageManagerConfigFactory::class)->create($config, $staticConfig, $filesystemsDisks);
            }
        );
    }

    private function registerDependenciesInjection(): void
    {
        $this->registerStorageManagerConfigSingleton();
        $this->app->singleton(
            EventDispatcher::class,
            fn (): EventDispatcher => new EventDispatcher()
        );
        $this->app->singleton(
            MimeTypeChecker::class,
            fn (): MimeTypeChecker => new MimeTypeChecker()
        );
        $this->app->singleton(
            LocalStorageStrategy::class,
            fn (Application $app): LocalStorageStrategy => new LocalStorageStrategy(
                $app->make(FilesystemFactory::class),
                $app->make(PathNormalizer::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            S3StorageStrategy::class,
            fn (Application $app): S3StorageStrategy => new S3StorageStrategy(
                $app->make(FilesystemFactory::class),
                $app->make(PathNormalizer::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            RemoteStorageStrategy::class,
            fn (Application $app): RemoteStorageStrategy => new RemoteStorageStrategy(
                $app->make(FilesystemFactory::class),
                $app->make(PathNormalizer::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            FilePropertyExtractor::class,
            fn (Application $app): FilePropertyExtractor => new FilePropertyExtractor(
                $app->make(StorageGateway::class)
            )
        );
        $this->app->singleton(
            FileMetadataExtractor::class,
            fn (Application $app): FileMetadataExtractor => new FileMetadataExtractor(
                $app->make(FilePropertyExtractor::class),
                $app->make(StorageGateway::class)
            )
        );
        $this->app->singleton(
            StorageGateway::class,
            fn (Application $app): StorageGateway => new StorageGateway(
                $app->make(FilesystemFactory::class),
                $app->make(PathNormalizer::class),
                $app->make(LocalStorageStrategy::class),
                $app->make(S3StorageStrategy::class),
                $app->make(RemoteStorageStrategy::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            PathConflictResolver::class,
            fn (Application $app): PathConflictResolver => new PathConflictResolver(
                $app->make(PathNormalizer::class),
                $app->make(StorageGateway::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            TrashService::class,
            fn (Application $app): TrashService => new TrashService(
                $app->make(StorageManagerConfig::class),
                $app->make(PathNormalizer::class),
                $app->make(LSMEventDispatcher::class),
                $app->make(StorageGateway::class),
                $app->make(PathConflictResolver::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            FileSearchService::class,
            fn (Application $app): FileSearchService => new FileSearchService(
                $app->make(PathNormalizer::class),
                $app->make(StorageGateway::class),
                $app->make(StorageManagerConfig::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            ThumbnailService::class,
            fn (Application $app): ThumbnailService => new ThumbnailService(
                $app->make(StorageManagerConfig::class),
                $app->make(StorageGateway::class)
            )
        );
        $this->app->singleton(
            ArchiveService::class,
            fn (Application $app): ArchiveService => new ArchiveService(
                $app->make(PathNormalizer::class),
                $app->make(StorageGateway::class),
                $app->make(StorageManagerConfig::class),
                $app->make(MimeTypeChecker::class),
                $app->make(LSMEventDispatcher::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            FileTransferService::class,
            fn (Application $app): FileTransferService => new FileTransferService(
                $app->make(PathNormalizer::class),
                $app->make(StorageGateway::class),
                $app->make(PathConflictResolver::class),
                $app->make(MimeTypeChecker::class),
                $app->make(StorageManagerConfig::class),
                $app->make(LSMEventDispatcher::class),
                $app->make(LoggerInterface::class),
                $app->make(PathSanitizer::class)
            )
        );
        $this->app->singleton(
            AclManager::class,
            fn (Application $app): AclManager => new AclManager(
                $app->make(StorageManagerConfig::class)
            )
        );
        $this->app->singleton(
            ActivityLogger::class,
            fn (Application $app): ActivityLogger => new ActivityLogger(
                $app->make(LSMEventDispatcher::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            DirectoryListingService::class,
            fn (Application $app): DirectoryListingService => new DirectoryListingService(
                $app->make(PathNormalizer::class),
                $app->make(StorageGateway::class),
                $app->make(StorageManagerConfig::class)
            )
        );
        $this->app->singleton(
            ActionExecutorService::class,
            fn (Application $app): ActionExecutorService => new ActionExecutorService(
                $app->make(Gate::class),
                $app->make(ActivityLogger::class),
                $app->make(AuthService::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->bind(Executor::class, ActionExecutorService::class);
        $this->app->singleton(
            UploadPathResolver::class,
            fn (Application $app): UploadPathResolver => new UploadPathResolver(
                $app->make(StorageManagerConfig::class),
                $app->make(FilesystemFactory::class)
            )
        );
        $this->app->singleton(
            UploadSessionService::class,
            fn (Application $app): UploadSessionService => new UploadSessionService(
                $app->make(UploadPathResolver::class),
                $app->make(StorageManagerConfig::class),
                $app->make(MimeTypeChecker::class),
                $app->make(StorageGateway::class),
                $app->make(PathSanitizer::class),
                $app->make(PathConflictResolver::class),
                $app->make(LSMEventDispatcher::class),
                $app->make(LoggerInterface::class)
            )
        );
        $this->app->singleton(
            DiskService::class,
            fn (Application $app): DiskService => new DiskService(
                $app->make(StorageManagerConfig::class),
                $app->make(LSMEventDispatcher::class),
                $app->make(CacheRepository::class),
                $app->make(StorageGateway::class)
            )
        );
        $this->app->singleton(
            AuthService::class,
            fn (Application $app): AuthService => new AuthService(
                $app->make(StorageManagerConfig::class),
                $app->make(AuthFactory::class),
                $app->make(Request::class)
            )
        );
    }

    public function registerAliases(): void
    {
        $this->app->alias(StorageManagerConfig::class, 'storage-manager.config');
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
            function (View $view): void {
                $storageManagerConfig = $this->app->make(StorageManagerConfig::class);
                $cacheRepository      = $this->app->make(CacheRepository::class);
                $composerJsonPath     = __DIR__ . '/../../../composer.json';
                $composer             = Arr::wrap($cacheRepository->remember(
                    'storage-manager-composer',
                    now()->addDay(),
                    fn () => json_decode(
                        file_get_contents($composerJsonPath) ?: '{}',
                        true
                    )
                ));
                $packageName         = strval($storageManagerConfig->getStaticConfig('packageName'));
                $composerPackageName = Arr::get($composer, 'name', $packageName);
                $composerPackageName = is_string($composerPackageName) ? $composerPackageName : $packageName;
                $version             = InstalledVersions::getVersion($composerPackageName);

                $packageLogo = route('storage-manager.assets', ['dir' => 'images', 'file' => 'favicon.svg']);
                $cspNonce    = $storageManagerConfig->frontend->getCspNonce();

                $view->with('cspNonce', $cspNonce);
                $view->with('useDevServer', $storageManagerConfig->frontend->devServerEnabled);
                $view->with('devServerUrl', $storageManagerConfig->frontend->devServerUrl);
                $view->with('storageManagerConfig', [
                    'cspNonce'            => $cspNonce,
                    'locale'              => App::getLocale(),
                    'translations'        => is_array($uiTrans = Lang::get('storage-manager::storage-manager.ui')) ? $uiTrans : [],
                    'routePrefix'         => '/' . trim($storageManagerConfig->route->prefix, '/'),
                    'packageName'         => $packageName,
                    'packageVersion'      => $version,
                    'packageLogo'         => $packageLogo,
                    'composerPackageName' => $composerPackageName,
                    'appDescription'      => Arr::get($composer, 'description'),
                    'appAuthors'          => Arr::get($composer, 'authors'),
                    'disks'               => Arr::map(
                        $storageManagerConfig->disks->getDisksMap(),
                        fn (Disk $_, string $diskName) => $diskName
                    ),
                    'disksConfig'         => Arr::map(
                        $storageManagerConfig->disks->getDisksMap(),
                        fn (Disk $disk): array => [
                            'name'              => $disk->name,
                            'driver'            => $disk->driver,
                            'readOnly'          => $disk->readOnly,
                            'supportsTrash'     => $storageManagerConfig->trash->enabled && $disk->trash,
                            'allowedExtensions' => $disk->allowedExtensions ?? $storageManagerConfig->upload->allowedExtensions,
                            'maxFileSize'       => $disk->maxFileSize       ?? $storageManagerConfig->upload->maxFileSize,
                            'filenamePolicy'    => $disk->filenamePolicy->value,
                            'duplicatePolicy'   => $disk->duplicatePolicy->value ?? $storageManagerConfig->upload->duplicatePolicy->value,
                        ]
                    ),
                    'readOnlyDisks'       => array_keys(array_filter(
                        $storageManagerConfig->disks->getDisksMap(),
                        fn (Disk $disk) => $disk->readOnly
                    )),
                    'allowedExtensions'   => $storageManagerConfig->upload->allowedExtensions,
                    'chunkMinSize'        => $storageManagerConfig->upload->chunkMinSize,
                    'chunkMaxSize'        => $storageManagerConfig->upload->chunkMaxSize,
                    'duplicatePolicy'     => $storageManagerConfig->upload->duplicatePolicy->value,
                    'sanitizeFilenames'   => $storageManagerConfig->upload->sanitizeFilenames,
                    'supportsZip'         => extension_loaded('zip')                             && class_exists(\ZipArchive::class),
                    'supportsThumbnails'  => $storageManagerConfig->thumbnails->thumbsAreEnabled && extension_loaded('gd'),
                    'supportsDiskStats'   => $storageManagerConfig->stats->enabled,
                    'supportsTrash'       => $storageManagerConfig->trash->enabled,
                    'routes'              => [
                        'fmInit'            => route('storage-manager.api.fm.init'),
                        'fmContent'         => route('storage-manager.api.fm.content'),
                        'fmSearch'          => route('storage-manager.api.fm.search'),
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
                        'disksStats'        => route('storage-manager.api.disks.stats'),

                        'trashList'         => route('storage-manager.api.trash.list'),
                        'trashRestore'      => route('storage-manager.api.trash.restore'),
                        'trashPurge'        => route('storage-manager.api.trash.purge'),
                        'trashEmpty'        => route('storage-manager.api.trash.empty'),
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

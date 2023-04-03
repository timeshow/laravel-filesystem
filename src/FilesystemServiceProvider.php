<?php
declare(strict_types=1);
namespace TimeShow\Filesystem;

use Illuminate\Support\ServiceProvider;
use League\Flysystem\Filesystem;
use Illuminate\Filesystem\FilesystemAdapter;
use TimeShow\Filesystem\Oss\OssAdapter;


class FilesystemServiceProvider extends ServiceProvider
{
    /**
     * The base package path.
     *
     * @var string|null
     */
    public static string|null $packagePath = null;

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot(): void
    {
        // 实现 oss 文件系统
        $this->extendOssStorage();

        self::$packagePath = __DIR__;

        $this->publishes(
            [
                self::$packagePath . '/config/filesystem.php' => config_path('filesystem.php'),
            ],
            'filesystem'
        );
    }

    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {

    }

    /**
     * 实现 Oss 文件系统
     */
    protected function extendOssStorage()
    {
        //Storage::extend('oss', function($app, $config){
        //    return new Filesystem(new OssAdapter($config), $config);
        //});

        app('filesystem')->extend('oss', function ($app, $config) {
            $root = $config['root'] ?? null;
            $buckets = $config['buckets'] ?? [];

            $adapter = new OssAdapter(
                $config['access_key'],
                $config['secret_key'],
                $config['endpoint'],
                $config['bucket'],
                $config['isCName'],
                $root,
                $buckets
            );

            return new FilesystemAdapter(new Filesystem($adapter), $adapter, $config);
        });
    }

}
<?php
namespace TimeShow\LaravelFilesystem;

use Illuminate\Support\ServiceProvider;

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
}
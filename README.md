# Laravel Filesystem


## Version Compatibility

Laravel      | Package
:-------------|:--------
9.0     | last version

## Install
Via Composer

``` bash
$ composer require timeshow/laravel-filesystem
```

If you want to use the repository generator through the `make:repository` Artisan command, add the `RepositoryServiceProvider` to your `config/app.php`:

``` php
TimeShow\Filesystem\FilesystemServiceProvider::class,
```

Publish the repostory configuration file.

``` bash
php artisan vendor:publish --tag="filesystem"
```

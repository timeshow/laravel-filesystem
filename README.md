# Laravel Filesystem


## Version Compatibility

Laravel      | Package
:-------------|:--------
9.0     | last version


## Require
Installation In order to install, just install
``` bash
PHP >=8.0
Laravel >=9.0
```

## Install
Via Composer

``` bash
$ composer require timeshow/laravel-filesystem
```

Then in your config/app.php add this line to providers array:

``` php
TimeShow\Filesystem\FilesystemServiceProvider::class,
```

## Configuration
Add the following in app/filesystems.php:

``` bash
    'oss'	=> [
        'driver' => 'oss',
        'root' => '',
        'access_key' => env('OSS_ACCESS_KEY'),
        'secret_key' => env('OSS_SECRET_KEY'),
        'endpoint' => env('OSS_ENDPOINT'), // 使用 ssl 这里设置如: https://oss-cn-beijing.aliyuncs.com
        'bucket' => env('OSS_BUCKET'),
        'isCName' => env('OSS_IS_CNAME', false),
        'securityToken'	=> null,
        'timeout' => '5184000',
        'connectTimeout' => '10',
        'transport' => 'http',//如果支持https，请填写https，如果不支持请填写http
        'max_keys' => 1000,//max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000
        // 如果有更多的 bucket 需要切换，就添加所有bucket，默认的 bucket 填写到上面，不要加到 buckets 中
        'buckets'=>[
            'test'=>[
                'access_key' => env('OSS_ACCESS_KEY'),
                'secret_key' => env('OSS_SECRET_KEY'),
                'bucket'     => env('OSS_TEST_BUCKET'),
                'endpoint'   => env('OSS_TEST_ENDPOINT'),
                'isCName'    => env('OSS_TEST_IS_CNAME', false),
            ],
            //...
        ],
    ],
```
Then in your .env add this lines to the bottom:
``` bash
OSS_ACCESS_KEY = 
OSS_SECRET_KEY = 
OSS_ENDPOINT = https://oss-cn-hangzhou.aliyuncs.com
OSS_BUCKET = 
OSS_IS_CNAME = false
```
Then you can set the default driver in app/filesystems.php:
```angular2html
'default' => 'oss',
```
Ok, well! You are finish to configure. Just feel free to use Aliyun OSS like Storage!

## Base Usage
First you must use Storage facade
```angular2html
use Illuminate\Support\Facades\Storage;
```
Then You can use all APIs of laravel Storage
```angular2html
// if default filesystems driver is oss, you can skip this step
$disk = Storage::drive('oss');

$disk->files($directory);
$disk->allFiles($directory);
void $disk->put('video/2', $file );
void $disk->putFile('path/to/file/file.jpg', 'local/path/to/local_file.jpg');

void $disk->write('file.md', 'contents');
void $disk->write('file.md', 'http://httpbin.org/robots.txt', ['options' => ['xxxxx' => 'application/redirect302']]);
void $disk->writeStream('file.md', fopen('path/to/your/local/file.jpg', 'r'));

$disk->get('path/to/file/file.jpg'); // get the file object by path
$disk->read('path/to/file/file.md'); // get the file object by path
bool $disk->exists('path/to/file/file.jpg'); // determine if a given file exists on the storage(OSS)
bool $disk->fileExists('path/to/file/file.md'); // determine if a given file exists on the storage(OSS)
int $disk->size('path/to/file/file.jpg'); // get the file size (Byte)
int $disk->fileSize('path/to/file/file.md'); // get the file size (Byte)
int $disk->lastModified('path/to/file/file.jpg'); // get date of last modification

$disk->directories($directory); // Get all of the directories within a given directory
$disk->allDirectories($directory); // Get all (recursive) of the directories within a given directory

void $disk->copy('old/file1.jpg', 'new/file1.jpg');
void $disk->move('old/file1.jpg', 'new/file1.jpg');
$disk->rename('path/to/file1.jpg', 'path/to/file2.jpg');

string $disk->mimeType('path/to/file1.jpg');

$disk->prepend('file.log', 'Prepended Text'); // Prepend to a file.
$disk->append('file.log', 'Appended Text'); // Append to a file.

bool $disk->delete('file.jpg');
bool $disk->delete(['file1.jpg', 'file2.jpg']);

bool $disk->createDirectory($directory); // Create a directory.
bool $disk->deleteDirectory($directory); // Recursively delete a directory.It will delete all files within a given directory, SO Use with caution please.

$disk->url('path/to/img.jpg') // get the file url
```

## Advanced Features
you must use Adapter
```angular2html
use Illuminate\Support\Facades\Storage;

array $disk->$adapter()->getMetadata('file.md');
array $disk->$adapter()->listContents();
string $disk->$adapter()->getUrl('file.md');
string $disk->getAdapter()->signatureConfig('path/to/file.png');

// url 访问有效期 & 图片处理「$timeout 为多少秒过期」
string $adapter->getTemporaryUrl('file.md', $timeout, ['x-oss-process' => 'image/circle,r_100']); // get
string $adapter->getTemporaryUrl('file.md', $timeout, ['x-oss-process' => 'image/circle,r_100'],'PUT'); // put

// 多个bucket切换
$adapter->bucket('test')->has('file.md');
```

## Full AliYun OSS Processing Capabilities
you can use more processing capabilities
```angular2html
$kernel = $adapter->ossKernel();

// 例如：防盗链功能
$refererConfig = new RefererConfig();
// 设置允许空Referer。
$refererConfig->setAllowEmptyReferer(true);
// 添加Referer白名单。Referer参数支持通配符星号（*）和问号（？）。
$refererConfig->addReferer("www.aliiyun.com");
$refererConfig->addReferer("www.aliiyuncs.com");

$kernel->putBucketReferer($bucket, $refererConfig);
```
Please refer to the official SDK manual : https://help.aliyun.com/document_detail/32100.html?spm=a2c4g.11186623.6.1055.66b64a49hkcTHv

## Web Configuration
```angular2html

/**
 * 1. 前缀如：'images/'
 * 2. 回调服务器 url
 * 3. 回调自定义参数，oss 回传应用服务器时会带上
 * 4. 当前直传配置链接有效期
 * 5. 文件大小限制
 * 6. 回调系统参数, 默认值: Iidestiny\Flysystem\Oss\OssAdapter::SYSTEM_FIELD
 */
$adapter->signatureConfig($prefix = '/', $callBackUrl = '', $customData = [], $expire = 30, $maxSize = 1024 * 1024 * 2, $systemData = ['etag' => '${etag}', 'filename' => '${object}']);
```

## Callback Verification


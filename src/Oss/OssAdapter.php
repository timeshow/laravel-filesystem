<?php
namespace TimeShow\Filesystem\Oss;

use League\Flysystem\Adapter\AbstractAdapter;
use League\Flysystem\Config;

class OssAdapter extends AbstractAdapter
{

    const FILE_TYPE_FILE    = 'file';//类型是文件
    const FILE_TYPE_DIR     = 'dir';//类型是文件夹

    /**
     * 配置信息
     * @var
     */
    protected $config;

    /**
     * oss client 上传对象
     * @var OssClient
     */
    protected $upload;

    /**
     * bucket
     * @var string
     */
    protected $bucket;

    /**
     * 构造方法
     * @param array $config   配置信息
     */
    public function __construct($config)
    {
        $this->config   = $config;
        $this->bucket   = $this->config['bucket'];
        //设置路径前缀
        $this->setPathPrefix($this->config['transport'] . '://' . $this->config['bucket'] . '.' .  $this->config['endpoint']);
    }

    /**
     * 格式化路径
     * @param $path
     * @return string
     */
    protected static function normalizerPath($path, $is_dir = false)
    {
        $path = ltrim(PathLibrary::normalizerPath($path, $is_dir), '/');

        return $path == '/' ? '' : $path;
    }

    /**
     * 获得OSS client上传对象
     * @return \OSS\OssClient
     */
    protected function getOss()
    {
        if (!$this->upload) {
            $this->upload = new OssClient(
                $this->config['accessKeyId'],
                $this->config['accessKeySecret'],
                $this->config['endpoint'],
                $this->config['isCName'],
                $this->config['securityToken']
            );

            //设置请求超时时间
            $this->upload->setTimeout($this->config['timeout']);

            //设置连接超时时间
            $this->upload->setConnectTimeout($this->config['connectTimeout']);
        }

        return $this->upload;
    }

    /**
     * 获得 Oss 实例
     * @return OssClient
     */
    public function getInstance()
    {
        return $this->getOss();
    }

    /**
     * 判断文件是否存在
     * @param string $path
     * @return bool
     */
    public function has($path)
    {
        try {
            return $this->getOss()->doesObjectExist($this->bucket, $path) != false ? true : false;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 读取文件
     * @param $file_name
     */
    public function read($path)
    {
        try {
            return ['contents' => $this->getOss()->getObject($this->bucket, static::normalizerPath($path)) ];
        }catch (OssException $e){

        }
        return false;

    }

    /**
     * 获得文件流
     * @param string $path
     * @return array|bool
     */
    public function readStream($path)
    {
        try {
            //获得一个临时文件
            $tmpfname       = FileFunction::getTmpFile();

            file_put_contents($tmpfname, $this->read($path)['contents'] );

            $handle         = fopen($tmpfname, 'r');

            //删除临时文件
            FileFunction::deleteTmpFile($tmpfname);

            return ['stream' => $handle];
        }catch (OssException $e){

        }

        return false;
    }

    /**
     * 写入文件
     * @param $file_name
     * @param $contents
     */
    public function write($path, $contents, Config $config)
    {
        try {
            $this->getOss()->putObject($this->bucket, $path, $contents, $option = []);

            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 写入文件流
     * @param string $path
     * @param resource $resource
     * @param array $config
     */
    public function writeStream($path, $resource, Config $config)
    {
        try{
            //获得一个临时文件
            $tmpfname       = FileFunction::getTmpFile();

            file_put_contents($tmpfname, $resource);

            $this->getOss()->uploadFile($this->bucket, $path, $tmpfname, $option = []);

            //删除临时文件
            FileFunction::deleteTmpFile($tmpfname);

            return true;
        }
        catch (OssException $e){

        }
        return false;
    }

    /**
     * 更新文件
     * @param string $path
     * @param string $contents
     * @param array $config
     */
    public function update($path, $contents, Config $config)
    {
        return $this->write($path, $contents, $config);
    }

    /**
     * 更新文件流
     * @param string $path
     * @param resource $resource
     * @param array $config
     */
    public function updateStream($path, $resource, Config $config)
    {
        return $this->writeStream($path, $resource, $config);
    }

    /**
     * 列出目录文件
     * @param string $directory
     * @param bool|false $recursive
     * @return array
     */
    public function listContents($directory = '', $recursive = false)
    {
        try{
            $directory = static::normalizerPath($directory, true);

            $options = [
                'delimiter' => '/' ,
                'prefix'    => $directory,
                'max-keys'  => $this->config['max_keys'],
                'marker'    => '',
            ];

            $result_obj = $this->getOss()->listObjects($this->bucket, $options);

            $file_list  = $result_obj->getObjectList();//文件列表
            $dir_list   = $result_obj->getPrefixList();//文件夹列表
            $data       = [];

            if (is_array($dir_list) && count($dir_list) > 0 ) {
                foreach ($dir_list as $key => $dir) {
                    $data[] = [
                        'path'      => $dir->getPrefix(),
                        'prefix'    => $options['prefix'],
                        'marker'    => $options['marker'],
                        'file_type' => self::FILE_TYPE_DIR
                    ];
                }
            }

            if (is_array($file_list) && count($file_list) > 0 ) {
                foreach ($file_list as $key => $file) {
                    if ($key == 0 ) {
                        $data[] = [
                            'path'      => $file->getKey(),
                            'prefix'    => $options['prefix'],
                            'marker'    => $options['marker'],
                            'file_type' => self::FILE_TYPE_DIR
                        ];
                    } else {
                        $data[] = [
                            'path'              => $file->getKey(),
                            'last_modified'     => $file->getLastModified(),
                            'e_tag'             => $file->getETag(),
                            'file_size'         => $file->getSize(),
                            'prefix'            => $options['prefix'],
                            'marker'            => $options['marker'],
                            'file_type'         => self::FILE_TYPE_FILE,
                        ];
                    }
                }
            }

            return $data;
        }catch (Exception $e){

        }
        return [];
    }

    /**
     * 获取资源的元信息，但不返回文件内容
     * @param $path
     * @return array|bool
     */
    public function getMetadata($path)
    {
        try {
            $file_info = $this->getOss()->getObjectMeta($this->bucket, $path);
            if ( !empty($file_info) ) {
                return $file_info;
            }
        }catch (OssException $e) {

        }
        return false;
    }

    /**
     * 获得文件大小
     * @param string $path
     * @return array
     */
    public function getSize($path)
    {
        $file_info = $this->getMetadata($path);
        return $file_info != false && $file_info['content-length'] > 0 ? [ 'size' => $file_info['content-length'] ] : ['size' => 0];
    }

    /**
     * 获得文件Mime类型
     * @param string $path
     * @return mixed string|null
     */
    public function getMimetype($path)
    {
        $file_info = $this->getMetadata($path);
        return $file_info != false && !empty($file_info['content-type']) ? [ 'mimetype' => $file_info['content-type'] ] : false;
    }

    /**
     * 获得文件最后修改时间
     * @param string $path
     * @return array 时间戳
     */
    public function getTimestamp($path)
    {
        $file_info = $this->getMetadata($path);
        return $file_info != false && !empty($file_info['last-modified'])
            ? ['timestamp' => strtotime($file_info['last-modified']) ]
            : ['timestamp' => 0 ];
    }

    /**
     * 获得文件模式 (未实现)
     * @param string $path
     */
    public function getVisibility($path)
    {
        return self::VISIBILITY_PUBLIC;
    }

    /**
     * 重命名文件
     * @param $oldname
     * @param $newname
     * @return boolean
     */
    public function rename($path, $newpath)
    {
        try {
            /**
             * 如果是一个资源，请保持最后不是以“/”结尾！
             *
             */
            $path = static::normalizerPath($path);

            $this->getOss()->copyObject($this->bucket, $path, $this->bucket, static::normalizerPath($newpath), []);
            $this->delete($path);
            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 复制文件
     * @param $path
     * @param $newpath
     * @return boolean
     */
    public function copy($path, $newpath)
    {
        try {
            $this->getOss()->copyObject($this->bucket, $path, $this->bucket, static::normalizerPath($newpath), []);
            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 删除文件或者文件夹
     * @param $path
     */
    public function delete($path)
    {
        try{
            $this->getOss()->deleteObject($this->bucket, $path);
            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 删除文件夹
     * @param string $path
     * @return mixed
     */
    public function deleteDir($path)
    {
        try{
            //递归去删除全部文件
            $this->recursiveDelete($path);

            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 递归删除全部文件
     * @param $path
     */
    protected function recursiveDelete($path)
    {
        $file_list = $this->listContents($path);

        // 如果当前文件夹文件不为空,则直接去删除文件夹
        if ( is_array($file_list) && count($file_list) > 0 ) {
            foreach ($file_list as $file) {
                if ($file['path'] == $path) {
                    continue;
                }
                if ($file['file_type'] == self::FILE_TYPE_FILE) {
                    $this->delete($file['path']);
                } else {
                    $this->recursiveDelete($file['path']);
                }
            }
        }

        $this->getOss()->deleteObject($this->bucket, $path);
    }

    /**
     * 创建文件夹
     * @param string $dirname
     * @param array $config
     */
    public function createDir($dirname, Config $config)
    {
        try{
            $this->getOss()->createObjectDir($this->bucket, static::normalizerPath($dirname, true));
            return true;
        }catch (OssException $e){

        }
        return false;
    }

    /**
     * 设置文件模式 (未实现)
     * @param string $path
     * @param string $visibility
     * @return bool
     */
    public function setVisibility($path, $visibility)
    {
        return true;
    }

    /**
     * 获取当前文件的URL访问路径
     * @param  string $file 文件名
     * @param  integer $expire_at 有效期，单位：秒
     * @return string
     */
    public function getUrl($file, $expire_at = 3600)
    {
        try {
            $accessUrl = $this->getOss()->signUrl($this->bucket, $file, $expire_at);
        } catch (OssException $e) {
            return false;
        }

        return $accessUrl;
    }




}
<?php

namespace Hangjw\BaiduDisk\Requests;

use Hangjw\BaiduDisk\Exceptions\UploadException;
use Hangjw\BaiduDisk\Traits\BaiduSendable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Upload
{

    use BaiduSendable;

    const CREATE_ERROR_CODE = 403;
    const PRE_ERROR_CODE    = 402;
    const UPLOAD_ERROR_CODE = 401;

    // 接口即时参数
    protected $header;
    protected $client;
    protected $logId;
    protected $uploadId;
    protected $path;
    protected $requestId;
    protected $toPath;
    protected $md5;
    protected $localPath;
    protected $result;

    // 需要配置
    protected $config;

    // 固定参数
    protected $channel = 'chunlei';
    protected $action = 'upload';
    protected $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134';
    protected $clientType = '0';
    protected $web = '1';
    protected $referer = 'https://pan.baidu.com/disk/home?errno=0&errmsg=Auth%20Login%20Sucess&&bduss=&ssnerror=0&traceid=';
    protected $superFileUrl = 'https://c3.pcs.baidu.com/rest/2.0/pcs/superfile2';
    protected $preCreateUrl = 'https://pan.baidu.com/api/precreate';
    protected $createUrl = 'https://pan.baidu.com/api/create';

    public function __get($key)
    {
        return $this->config->{$key};
    }

    public function __construct(BaiduConfig $config)
    {
        $this->config = $config;
        $this->client = new Client();
        $this->header = [
            'User-Agent' => $this->agent,
            'Cookie'     => $this->config->superCookies,
        ];
    }

    protected function post()
    {
        $path = $this->localPath;
        $uploadUrl = $this->superFileUrl;

        $uploadQueryParam = [
            'method'     => $this->action,
            'app_id'     => $this->config->appId,
            'channel'    => $this->channel,
            'clienttype' => $this->clientType,
            'web'        => $this->web,
            'BDUSS'      => $this->replaceBd($this->config->bduss), //$this->bduss,
            'logid'      => $this->logId,
            'path'       => $this->replaceBd($this->path),
            'uploadid'   => $this->uploadId,
            'uploadsign' => '0',
            'partseq'    => '0',
        ];

        $multipart = [
            [
                'name'     => 'file',
                'filename' => 'blob',
                'contents' => fopen($path, 'r'),
                'headers'  => [
                    'Content-Disposition' => 'form-data; name="file"; filename="blob"',
                    'Content-Type'        => 'application/octet-stream',
                ],
            ],
        ];

        $url = $uploadUrl . '?' . ($this->getQuery($uploadQueryParam));
        try {
            $options = [
                'multipart' => $multipart,
                'headers'   => $this->header,
            ];
            $response = $this->client->request('POST', $url, $options);
        } catch (ClientException $clientException) {
            throw new UploadException($clientException->getMessage(), self::UPLOAD_ERROR_CODE);
        }


        $result = ((string)$response->getBody());
        $data = json_decode($result, true);
        $this->md5 = $data['md5'];
        $this->requestId = $data['request_id'];
    }


    protected function setToPath($toPath)
    {
        $this->toPath = $toPath;
    }

    protected function preCreate()
    {
        $createUrl = $this->preCreateUrl;
        $createQueryParam = [
            'channel'      => $this->channel,
            'web'          => $this->web,
            'app_id'       => $this->config->appId,
            'bdstoken'     => $this->config->bdsToken,
            'logid'        => $this->logId,
            'clienttype'   => $this->clientType,
            'startLogTime' => time() - mt_rand(1000, 10000) + mt_rand(100, 999),
        ];
        $createFormParam = [
            'autoinit'    => '1',
            'block_list'  => '["5910a591dd8fc18c32a8f3df4fdc1761"]',
            'local_mtime' => time() - mt_rand(1000, 10000),
            'path'        => $this->toPath,
        ];

        $preCreateHeaders = [
            'User-Agent' => $this->agent,
            'Referer'    => $this->referer,
            'Cookie'     => $this->config->panCookies,
        ];

        $url = $createUrl . '?' . ($this->getQuery($createQueryParam));

        try {
            $data = [
                'form_params' => $createFormParam,
                'headers'     => $preCreateHeaders,
            ];
            $response = $this->client->request('POST', $url, $data);
        } catch (ClientException $clientException) {
            throw new UploadException($clientException->getMessage(), self::PRE_ERROR_CODE);
        }


        $result = ((string)$response->getBody());

        $data = json_decode($result, true);
        $this->path = $data['path'];
        $this->uploadId = $data['uploadid'];
        $this->requestId = $data['request_id'];
    }


    public function create()
    {
        $createQueryParam = [
            'isdir'      => '0',
            'rtype'      => '1',
            'channel'    => $this->channel,
            'web'        => $this->web,
            'app_id'     => $this->config->appId,
            'bdstoken'   => $this->config->bdsToken,
            'logid'      => $this->logId, //'MTUzMjA1NjkwMTA3NDAuODc2NDI2MTQ3NjczODUwOQ',
            'clienttype' => $this->clientType,
        ];

        $createFormParam = [
            'block_list'  => '["' . $this->md5 . '"]',
            'local_mtime' => time() - mt_rand(3000, 3600),
            'path'        => $this->toPath,
            'size'        => strlen(file_get_contents($this->localPath)),
            'uploadid'    => $this->uploadId, //'N1-MTgzLjE1Ny42Ni4xNTM6MTUzMjA1NjkwMDo0NjQ5NTQzNzMzNjc4Njg2MjA4',
        ];

        $url = $this->createUrl . '?' . ($this->getQuery($createQueryParam));

        try {
            $options = [
                'form_params' => $createFormParam,
                'headers'     => [
                    'User-Agent' => $this->agent,
                    'Cookie'     => $this->config->panCookies,
                ],
            ];
            $response = $this->client->request('POST', $url, $options);
        } catch (ClientException $clientException) {
            throw new UploadException($clientException->getMessage(), self::CREATE_ERROR_CODE);
        }

        $this->result = json_decode((string)$response->getBody(), true);
    }

    protected function setLocalPath($path)
    {
        $this->localPath = $path;
    }

    public function uploadFile($path, $toPath = null)
    {
        $toPath = $toPath ?: '/api/' . md5(Str::uuid()) . '.png';
        // 生成LogId
        $this->buildLogId();
        // 设置本地地址
        $this->setLocalPath($path);
        // 设置储存地址
        $this->setToPath($toPath);
        // 预创建
        $this->preCreate();
        // 上传文件
        $this->post();
        // 获取文件信息
        $this->create();

        return $this;
    }


    public function getResult()
    {
        return $this->result;
    }
}
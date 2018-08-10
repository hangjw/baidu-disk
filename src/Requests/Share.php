<?php

namespace Hangjw\BaiduDisk\Requests;

use Hangjw\BaiduDisk\Exceptions\ShareException;
use Hangjw\BaiduDisk\Traits\BaiduSendable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Share
{

    use BaiduSendable;

    const LINK_ERROR_CODE    = 401;
    const REQUEST_ERROR_CODE = 402;

    protected $client;
    protected $logId;
    protected $uploadId;
    protected $path;
    protected $requestId;
    protected $toPath;
    protected $md5;
    protected $localPath;
    protected $result;
    protected $resultUrl;

    // 需要配置
    protected $config;
    protected $appId = null;
    protected $panCookies = null;
    protected $superCookies = null;
    protected $bduss = null;

    // 定死
    protected $channel = 'chunlei';
    protected $action = 'upload';
    protected $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134';
    protected $clientType = '0';
    protected $web = '1';
    protected $referer = 'https://pan.baidu.com/disk/home?errno=0&errmsg=Auth%20Login%20Sucess&&bduss=&ssnerror=0&traceid=';
    protected $shareUrl = 'https://pan.baidu.com/share/set';

    public function __get($key)
    {
        return $this->config->{$key};
    }

    public function __construct(BaiduConfig $config)
    {
        $this->config = $config;
        $this->client = new Client();
    }


    public function shareByUploadResult($uploadResult)
    {
        return $this->goShare($uploadResult['fs_id']);
    }

    public function shareByFsId($fsId)
    {
        return $this->goShare($fsId);
    }

    protected function goShare($id)
    {
        $this->buildLogId();
        $uploadUrl = $this->shareUrl;

        $shareHeaders = [
            'User-Agent' => $this->agent,
            'Referer'    => 'https://pan.baidu.com/disk/home',
            'Cookie'     => $this->config->panCookies,
        ];

        $shareQueryParams = [
            'channel'    => [$this->channel, $this->channel],
            'app_id'     => $this->config->appId,
            'bdstoken'   => $this->config->bdsToken,
            'clienttype' => [$this->clientType, $this->clientType],
            'logid'      => $this->logId,
            'web'        => [$this->web, $this->web],
        ];

        $shareFormParams = [
            'channel_list' => '[]',
            'fid_list'     => '[' . $id . ']',
            'period'       => '0',
            'schannel'     => '0',
        ];

        $url = $uploadUrl . '?' . ($this->getQuery($shareQueryParams));
        try {
            $response = $this->client->request('POST', $url, [
                'form_params' => $shareFormParams,
                'headers'     => $shareHeaders,
            ]);
        } catch (ClientException $clientException) {
            throw new ShareException($clientException->getMessage(), self::REQUEST_ERROR_CODE);
        }

        $this->result = json_decode((string)$response->getBody(), true);
        if (empty($this->result['link'])) {
            throw new ShareException('link empty' . var_export($this->result, true), self::LINK_ERROR_CODE);
        }
        return $this;
    }

    public function getSrc()
    {
        return $this->result['link'];
    }

    public function getShareId()
    {
        return $this->result['shareid'];
    }


}
<?php

namespace Hangjw\BaiduDisk;

use Hangjw\BaiduDisk\Traits\BaiduSendable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Share
{

    use BaiduSendable;

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
    protected $bdsToken = null;

    // 定死
    protected $channel = 'chunlei';
    protected $action = 'upload';
    protected $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134';
    protected $clientType = '0';
    protected $web = '1';
    protected $referer = 'https://pan.baidu.com/disk/home?errno=0&errmsg=Auth%20Login%20Sucess&&bduss=&ssnerror=0&traceid=';
    protected $shareUrl = 'https://pan.baidu.com/share/set';


    public function __construct(BaiduConfig $config)
    {
        $this->init(Config::get('baiduDisk'));
        $this->config = $config;
        $this->client = new Client();
    }

    protected function init($config)
    {
        $this->appId = $config['app_id'];
        $this->panCookies = $config['cookies'];
        $this->superCookies = $config['cookies'];
        $this->bduss = $config['bduss'];
        $this->bdsToken = $config['bds_token'];
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
            'Referer' => 'https://pan.baidu.com/disk/home',
            'Cookie'     => $this->panCookies,
        ];

        $shareQueryParams = [
            'channel' => [$this->channel, $this->channel],
            'app_id'     => $this->appId,
            'bdstoken'   => $this->bdsToken,
            'clienttype' => [$this->clientType, $this->clientType],
            'logid' => $this->logId,
            'web' => [$this->web, $this->web]
        ];

        $shareFormParams = [
            'channel_list' => 	'[]',
            'fid_list' => 	'[' . $id . ']',
            'period' => 	'0',
            'schannel' => 	'0',
        ];

        $url = $uploadUrl . '?' . ($this->getQuery($shareQueryParams));
        try {
            $response = $this->client->request('POST', $url, [
                'form_params' => $shareFormParams,
                'headers' => $shareHeaders,
            ]);
        } catch (ClientException $clientException) {
            Log::error('share error, msg:' . $clientException->getMessage() . "\n param:" .var_export($url, true) . var_export($shareFormParams, true) . var_export($shareHeaders, true));
        }

        $this->result = json_decode((string)$response->getBody(), true);
        if (empty($this->result['link']))  {
            Log::error('share result error, msg:' . (string) $response->getBody());
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
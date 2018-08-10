<?php

namespace Hangjw\BaiduDisk\Requests;

use Hangjw\BaiduDisk\Traits\BaiduSendable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Token
{

    protected $agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/64.0.3282.140 Safari/537.36 Edge/17.17134';
    protected $mainUrl = 'https://pan.baidu.com/disk/home';
    protected $referer = 'https://pan.baidu.com/disk/home?errno=0&errmsg=Auth%20Login%20Sucess&&bduss=&ssnerror=0&traceid=';

    public function __construct(BaiduConfig $config)
    {
        $this->config = $config;
        $this->client = new Client();
    }

    public function get()
    {
        $preCreateHeaders = [
            'User-Agent' => $this->agent,
            'Referer'    => $this->referer,
            'Cookie'     => $this->config->cookies,
        ];

        $options = [
            'headers'   => $preCreateHeaders,
        ];
        $response = $this->client->get($this->mainUrl, $options);
        $result = ((string)$response->getBody());
        if (preg_match("/initPrefetch\('(.*)',/", $result, $res)) {
            $bdToken = $res[1];
        }
        return $bdToken ?? null;
    }

}
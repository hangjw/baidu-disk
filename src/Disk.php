<?php

namespace Hangjw\BaiduDisk;

use Hangjw\BaiduDisk\Requests\Share;
use Hangjw\BaiduDisk\Requests\Token;
use Hangjw\BaiduDisk\Requests\Upload;
use Hangjw\Exceptions\UploadException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Disk
{

    protected $upload;
    protected $share;


    public function __construct($config)
    {
        // 初始化配置
        $config = new BaiduConfig($config);
        $config->bdsToken = (new Token($config))->get();

        // 初始化模型
        $this->upload = new Upload($config);
        $this->share = new Share($config);
    }

    public function upload($sourcePath, $toPath = '/')
    {
        return $this->upload->uploadFile($sourcePath, $toPath);
    }

    public function getShareUrlByResult($result)
    {
        $share = $this->share->shareByUploadResult($result);
        return $share->getSrc();
    }
}
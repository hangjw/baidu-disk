<?php

namespace Hangjw\BaiduDisk;

use Hangjw\Exceptions\BaiduException;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Config;
use Hangjw\BaiduDisk\Config as BaiduConfig;

class Disk
{

    protected $upload;
    protected $share;

    public function __construct()
    {
        $config = new BaiduConfig(Config::get('baiduDisk'));
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
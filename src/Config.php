<?php

namespace Hangjw\BaiduDisk;

use Illuminate\Support\Facades\Config as laravelConfig;

class Config
{

    protected $appId;
    protected $panCookies;
    protected $superCookies;
    protected $bduss;
    protected $bdsToken;

    public function __construct($configs)
    {
        foreach($configs as $key => $config) {
            $this->set($key, $config);
        }
    }

    public function get($key)
    {
        return $this->key ?? null;
    }

    public function set($key, $config)
    {
        $this->key = $config;
    }

}
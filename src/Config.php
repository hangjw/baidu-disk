<?php

namespace Hangjw\BaiduDisk;

use Illuminate\Support\Facades\Config as laravelConfig;

class Config
{

    public $appId;
    public $panCookies;
    public $superCookies;
    public $bduss;
    public $bdsToken;
    public $cookies;

    public function __construct($configs)
    {
        foreach ($configs as $key => $config) {
            $this->{$this->studly($key)} = $config;
        }
        $this->panCookies = $configs['cookies'];
        $this->superCookies = $configs['cookies'];
    }

    public function studly($value)
    {
        $value = ucwords(str_replace(['-', '_'], ' ', $value));
        return lcfirst(str_replace(' ', '', $value));
    }
}
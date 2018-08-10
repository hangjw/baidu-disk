<?php

namespace Hangjw\BaiduDisk;


class BaiduDisk
{
    protected static $disk;

    public static function __callStatic($name, $arguments)
    {
        if (empty(self::$disk)) {
            self::$disk = new Disk();
        }
        return (self::$disk)->$name(...$arguments);
    }

}
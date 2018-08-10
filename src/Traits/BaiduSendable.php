<?php

namespace Hangjw\BaiduDisk\Traits;

trait BaiduSendable
{

    protected function getQuery($array)
    {
        $str = '';
        foreach ($array as $key => $row) {
            if (is_array($row)) {
                foreach ($row as $li) {
                    $str .= $key . '=' . $li . '&';
                }
            } else {
                $str .= $key . '=' . $row . '&';
            }
        }
        return trim($str, '&');
    }

    protected function replaceBd($row)
    {
        $row = str_replace('/', '%2F', $row);
        $row = str_replace('+', '%2B', $row);
        $row = str_replace('=', '%3D', $row);
        return $row;
    }

    protected function buildLogId()
    {
        $repeat = '';
        for ($i = 0; $i < 16; $i++) {
            $repeat .= mt_rand(0, 9);
        }
        $time = time() . mt_rand(1000, 9999) . '.' . $repeat;
        $logId = base64_encode($time);
        $this->logId = $logId;
    }

}
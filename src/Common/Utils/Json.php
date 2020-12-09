<?php

namespace App\Common\Utils;

class Json
{
    public static function open(string $filename)
    {
        $data = file_get_contents($filename);
        $data = \GuzzleHttp\json_decode($data);
        return $data;
    }
    
    public static function save(string $filename, $data)
    {
        $data = \GuzzleHttp\json_encode($data);
        file_put_contents($filename, $data);
    }
}

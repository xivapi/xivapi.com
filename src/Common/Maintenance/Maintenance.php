<?php

namespace App\Common\Maintenance;

class Maintenance
{
    public function check()
    {
        $file = __DIR__.'/../../../maintenance.txt';
        
        if (file_exists($file) && trim(file_get_contents($file)) == 'on') {
            echo file_get_contents(__DIR__.'/template.html');
            header('HTTP/1.1 503 Service Temporarily Unavailable');
            header('Status: 503 Service Temporarily Unavailable');
            header('Retry-After: 300');
            die;
        }
    }
}

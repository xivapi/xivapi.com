<?php

namespace App\Service\Common;

class Time
{
    /**
     * '%a days, %h hours, %i minutes and %s seconds'
     */
    public static function countdown($seconds, $format = '%a days, %h hours')
    {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format($format);
    }
}

<?php

namespace App\Service\User\SSO;

class SSOAuth
{
    public $url;
    public $state;

    public function __construct($url, $state)
    {
        $this->url = $url;
        $this->state = $state;
    }
    
    public function getUrl()
    {
        return $this->url;
    }
    
    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }
    
    public function getState()
    {
        return $this->state;
    }
    
    public function setState($state)
    {
        $this->state = $state;
        return $this;
    }
}

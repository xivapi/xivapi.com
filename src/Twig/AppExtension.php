<?php

namespace App\Twig;

use App\Service\Common\Environment;
use App\Service\Common\SiteVersion;
use Carbon\Carbon;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('dateRelative', [$this, 'getDateRelative']),
        ];
    }
    
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('environment', [$this, 'getEnvironment']),
            new \Twig_SimpleFunction('siteVersion', [$this, 'getApiVersion']),
            new \Twig_SimpleFunction('favIcon',     [$this, 'getFavIcon']),
            new \Twig_SimpleFunction('env',         [$this, 'getEnvVariable']),
        ];
    }
    
    /**
     * Get an environment variable
     */
    public function getEnvVariable($string)
    {
        return getenv($string);
    }
    
    /**
     * Show a date in relative terms
     */
    public function getDateRelative($unix)
    {
        $unix = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = time() - $unix;
        
        // if over 72hrs, show date
        if ($difference > (60 * 60 * 72)) {
            return date('M jS', $unix);
        }
        
        return Carbon::now()->subSeconds($difference)->diffForHumans();
    }

    /**
     * Get the current site environment
     */
    public function getEnvironment()
    {
        return constant(Environment::CONSTANT);
    }

    /**
     * Get API version information
     */
    public function getApiVersion()
    {
        return SiteVersion::get();
    }

    /**
     * Get Fav icon based on if the site is in dev or prod mode
     */
    public function getFavIcon()
    {
        return getenv('APP_ENV') == 'dev' ? '/favicon_dev.png' : '/favicon.png';
    }
}

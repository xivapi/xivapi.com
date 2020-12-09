<?php

namespace App\Common\Twig;

use App\Common\Service\Redis\Redis;
use App\Common\Utils\Environment;
use App\Common\Utils\Language;
use App\Common\Utils\SiteVersion;
use App\Common\Utils\Time;
use Carbon\Carbon;
use Carbon\CarbonTimeZone;
use Delight\Cookie\Cookie;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension
{
    public function getFilters()
    {
        return [
            new TwigFilter('timestamp',     [$this, 'timestamp']),
            new TwigFilter('date',          [$this, 'getDate']),
            new TwigFilter('dateSimple',    [$this, 'getDateSimple']),
            new TwigFilter('dateRelative',  [$this, 'getDateRelative']),
            new TwigFilter('bool',          [$this, 'getBoolVisual']),
            new TwigFilter('max',           [$this, 'getMaxValue']),
            new TwigFilter('min',           [$this, 'getMinValue']),
        ];
    }

    public function getFunctions()
    {
        return [
            new TwigFunction('env',         [$this, 'getEnvVariable']),
            new TwigFunction('environment', [$this, 'getEnvironment']),
            
            new TwigFunction('siteVersion', [$this, 'getVersion']),
            new TwigFunction('favIcon',     [$this, 'getFavIcon']),
            
            new TwigFunction('cache',       [$this, 'getCached']),
            new TwigFunction('timezone',    [$this, 'getTimezone']),
            new TwigFunction('timezones',   [$this, 'getTimezones']),
            new TwigFunction('cookie',      [$this, 'getCookie']),
        ];
    }

    public function getTimestamp($string)
    {
        return (int)(new \DateTime($string, Time::timezone()))->format("U");
    }
    
    /**
     * Get date in a nice format.
     */
    public function getDate($unix)
    {
        $unix       = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = abs(time() - $unix);
        $carbon     = $unix > time()
            ? Carbon::now()->addSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()))
            : Carbon::now()->subSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()));

        if ($difference > (60 * 60 * 24)) {
            return $carbon->format('jS M, H:i:s');
        }

        return $carbon->diffForHumans();
    }

    /**
     * Get date in a nice format.
     */
    public function getDateSimple($unix)
    {
        $unix       = is_numeric($unix) ? $unix : strtotime($unix);
        $difference = time() - $unix;
        $carbon     = Carbon::now()->subSeconds($difference)->setTimezone(new CarbonTimeZone(Time::timezone()));

        if ($difference > (60 * 60 * 24)) {
            return $carbon->format('j M, H:i');
        }

        return $carbon->diffForHumans();
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
     * Get an environment variable
     */
    public function getEnvVariable($string)
    {
        return getenv($string);
    }
    
    /**
     * Get the current site environment
     */
    public function getEnvironment()
    {
        return constant(Environment::CONSTANT);
    }

    /**
     * Get Users Timezone
     */
    public function getTimezone()
    {
        return Time::timezone();
    }
    
    /**
     * Get supported timezones
     */
    public function getTimezones()
    {
        return Time::timezones();
    }
    
    /**
     * Get a cookie value
     */
    public function getCookie($value, $defaultValue = null)
    {
        return Cookie::get($value) ?: $defaultValue;
    }

    /**
     * Renders a tick or cross for bool visuals
     */
    public function getBoolVisual($bool)
    {
        return $bool ? '✔' : '✘';
    }

    /**
     * Return max value in an array
     */
    public function getMaxValue($array)
    {
        return $array ? max($array) : 0;
    }

    /**
     * Return min value in an array
     */
    public function getMinValue($array)
    {
        return $array ? min($array) : 0;
    }

    /**
     * Get API version information
     */
    public function getVersion()
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

    /**
     * Get static cache
     */
    public function getCached($key)
    {
        $obj = Redis::Cache()->get($key);
        $obj = Language::handle($obj);
        return $obj;
    }
}

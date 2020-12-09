<?php

namespace App\Common\Utils;

use Delight\Cookie\Cookie;
use Symfony\Component\HttpFoundation\Request;

/**
 * Handle each of the FFXIV Game Languages
 */
class Language
{
    const DEFAULT = 'en';
    const LANGUAGES = [
        'en',
        'de',
        'fr',
        'ja',
        'kr',
        'cn'
    ];
    const LANGUAGES_ACTIVE = [
        'en',
        'de',
        'fr',
        'ja',
    ];

    private static $lang = self::DEFAULT;

    /**
     * Confirm a language param provided is legit
     */
    public static function confirm($language)
    {
        if (in_array($language, self::LANGUAGES)) {
            return $language;
        }

        return self::DEFAULT;
    }
    
    /**
     * Set the language for the API
     */
    public static function register(Request $request): void
    {
        self::$lang = $request->get('language') ?? self::userLanguage();
        
        if (!in_array(self::$lang, self::LANGUAGES)) {
            self::$lang = self::userLanguage();
        }
    }

    /**
     * Return the current language
     */
    public static function current(): string
    {
        return self::$lang;
    }
    
    /**
     * Convert an array of data into a specific language
     */
    public static function handle($data, string $language = null)
    {
        $language = substr(strtolower($language ?: self::$lang), 0, 2);

        if (!in_array($language, self::LANGUAGES)) {
            $language = self::LANGUAGES[0];
        }
        
        if (is_object($data)) {
            $data = json_decode(json_encode($data), true);
        }
        
        if (!$data) {
            return $data;
        }
    
        foreach ($data as $i => $value) {
            if (is_array($value)) {
                $data[$i] = self::handle($value);
            }
            
            $postfix = '_'. $language;
            if (strpos($i, $postfix) !== false) {
                $data[str_ireplace($postfix, null, $i)] = $value;
            }
        }
        
        return $data;
    }

    /**
     * Get the users language
     */
    public static function userLanguage()
    {
        return Cookie::get('mogboard_language') ?: self::DEFAULT;
    }
}

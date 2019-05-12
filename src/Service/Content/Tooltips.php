<?php

namespace App\Service\Content;

use App\Common\Utils\Language;
use App\Exception\InvalidTooltipsColumnCountException;
use App\Exception\InvalidTooltipsContentCountException;
use App\Exception\InvalidTooltipsIdCountException;
use App\Common\Utils\Arrays;

class Tooltips
{
    const MAX_CONTENT   = 10;
    const MAX_IDS       = 100;
    const MAX_COLUMNS   = 50;
    
    /** @var array */
    private $response = [];
    /** @var array */
    private $globalColumns = [];
    /** @var GameData */
    private $gamedata;
    
    public function __construct(GameData $gamedata)
    {
        $this->gamedata = $gamedata;
    }
    
    /**
     * Handle tooltip requests
     */
    public function handle($json)
    {
        // grab global columns
        $this->globalColumns = $json->_columns ?? [];
        unset($json->_columns);
        
        if (count($json) > self::MAX_CONTENT) {
            throw new InvalidTooltipsContentCountException();
        }
        
        // loop through content
        foreach ($json as $contentName => $request) {
            $columns = $request->columns ?? [];
            $columns = array_merge($columns, $this->globalColumns);
            
            if (count($request->ids) > self::MAX_IDS) {
                throw new InvalidTooltipsIdCountException();
            }
            
            if (count($columns) > self::MAX_COLUMNS) {
                throw new InvalidTooltipsColumnCountException();
            }
            
            foreach ($request->ids as $id) {
                $content = $this->gamedata->one($contentName, $id);
                $content = Language::handle($content);
                $columns = Arrays::extractColumnsCount($content, $columns);
                $columns = Arrays::extractMultiLanguageColumns($columns);
                $content = Arrays::extractColumns($content, $columns);
                $this->response[$contentName][$id] = $content;
                unset($content);
            }
        }
        
        return $this->response;
    }
}

<?php

namespace App\Service\Search;

use App\Exception\InvalidSearchRequestException;
use App\Service\Common\Language;
use Symfony\Component\HttpFoundation\Request;

class SearchRequest
{
    const STRING_CUSTOM              = 'custom';
    const STRING_WILDCARD            = 'wildcard';
    const STRING_WILDCARD_PLUS       = 'wildcard_plus';
    const STRING_FUZZY               = 'fuzzy';
    const STRING_TERM                = 'term';
    const STRING_PREFIX              = 'prefix';
    const STRING_MATCH               = 'match';
    const STRING_MATCH_PHRASE        = 'match_phrase';
    const STRING_MATCH_PHRASE_PREFIX = 'match_phrase_prefix';
    const STRING_MULTI_MATCH         = 'multi_match';
    const STRING_QUERY_STRING        = 'query_string';
    const STRING_SIMILAR             = 'similar';

    const MIN_LIMIT = 1;
    const MAX_LIMIT = 100;

    const STRING_ALGORITHM_DEFAULT = self::STRING_WILDCARD;
    const STRING_ALGORITHMS = [
        self::STRING_CUSTOM,
        self::STRING_WILDCARD,
        self::STRING_WILDCARD_PLUS,
        self::STRING_FUZZY,
        self::STRING_TERM,
        self::STRING_PREFIX,
        self::STRING_MATCH,
        self::STRING_MATCH_PHRASE,
        self::STRING_MATCH_PHRASE_PREFIX,
        self::STRING_MULTI_MATCH,
        self::STRING_QUERY_STRING,
        self::STRING_SIMILAR,
    ];
    
    // specific indexes
    public $type = 'search';
    /** @var string|array */
    public $indexes = SearchContent::LIST_DEFAULT;
    // the search string
    public $string = '';
    // the string query algorithm to use
    public $stringAlgo = self::STRING_ALGORITHM_DEFAULT;
    // string search column
    public $stringColumn = 'NameCombined_%s';
    // list of filters
    public $filters = '';
    // sort field
    public $sortField;
    // sort order
    public $sortOrder = 'asc';
    // limit (per page)
    public $limit = 100;
    // limit start
    public $limitStart = 0;
    // page to start from
    public $page = 1;
    // language
    public $language = Language::DEFAULT;
    // columns
    public $columns = '_,_Score,ID,Icon,Name,Url,UrlType';
    public $columnsLore = 'Text,Context,Source,SourceID';
    // query group
    public $bool = 'must';
    /** @var null|string|array */
    public $body = null;
    
    /**
     * Build the search request from the http request
     */
    public function buildFromRequest(Request $request)
    {
        $this->indexes          = $request->get('indexes',        $this->indexes);
        $this->string           = $request->get('string',         $this->string);
        $this->stringAlgo       = $request->get('string_algo',    $this->stringAlgo);
        $this->stringColumn     = $request->get('string_column',  $this->stringColumn);
        $this->page             = $request->get('page',           $this->page);
        $this->sortField        = $request->get('sort_field',     $this->sortField);
        $this->sortOrder        = $request->get('sort_order',     $this->sortOrder);
        $this->limit            = $request->get('limit',          $this->limit);
        $this->language         = $request->get('language',       $this->language);
        $this->filters          = $request->get('filters',        $this->filters);
        $this->columns          = $request->get('columns',        $this->columns);
        $this->bool             = $request->get('bool',           $this->bool);
        $this->body             = $request->getContent() ?: null;
        
        // ensure body requests is in the array format
        if ($this->body) {
            $this->body = json_decode($this->body, true)['body'] ?? null;
        }
        
        // this ensures response handler will use default search columns
        if (empty($request->get('columns'))) {
            $request->request->set(
                'columns',
                $this->indexes == 'lore_finder' ? $this->columnsLore : $this->columns
            );
        }
        
        // validate indexes
        $this->indexes = is_array($this->indexes) ? $this->indexes : explode(',', $this->indexes);
        $this->indexes = array_map('strtolower', $this->indexes);
        $this->indexes = SearchContent::validate($this->indexes);
        $this->indexes = implode(',', $this->indexes);
        
        // check limit
        $this->limit = $this->limit >= self::MIN_LIMIT ? $this->limit : self::MIN_LIMIT;
        $this->limit = $this->limit <= self::MAX_LIMIT ? $this->limit : self::MAX_LIMIT;
        
        // Override some
        $this->page             = $this->page < 1 ? 1 : $this->page;
        $this->limitStart       = ($this->limit * ($this->page - 1));
        
        // override by what is allowed
        $this->language         = Language::confirm($this->language);
        
        // lower case it for the sake of performance and analyzer matching
        $this->string           = str_ireplace('+', ' ', strtolower($this->string));
        $this->stringAlgo       = in_array($this->stringAlgo, self::STRING_ALGORITHMS) ? $this->stringAlgo : self::STRING_CUSTOM;
        $this->stringColumn     = sprintf($this->stringColumn, $this->language);
    }
}

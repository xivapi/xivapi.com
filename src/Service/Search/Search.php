<?php

namespace App\Service\Search;

use App\Service\SearchElastic\ElasticSearch;
use App\Service\SearchElastic\ElasticQuery;

class Search
{
    /** @var ElasticSearch $search */
    public $search;
    /** @var ElasticQuery $query */
    public $query;

    function __construct()
    {
        [$ip, $port] = explode(',', getenv('ELASTIC_SERVER_LOCAL'));
        $this->search = new ElasticSearch($ip, $port);

        // create elastic query
        $this->query = new ElasticQuery();
    }
    
    /**
     * @throws \Exception
     */
    public function handleRequest(SearchRequest $req, SearchResponse $res)
    {
        // if a payload exists
        if ($req->body) {
            $this->handleBodyRequest($req, $res);
            return;
        }

        $this->handleGetRequest($req, $res);
    }
    
    /**
     * Perform a body query by using the query provided in the body payload
     */
    private function handleBodyRequest(SearchRequest $req, SearchResponse $res)
    {
        $res->setResults(
            $this->search->search($req->indexes, 'search', $req->body) ?: []
        );
    }
    
    /**
     * Perform a normal "GET" request by auto-building a query based on provided params
     */
    private function handleGetRequest(SearchRequest $req, SearchResponse $res)
    {
        //
        // Sorting
        //
        if ($req->sortField) {
            $this->query->sort([
                [ $req->sortField, $req->sortOrder ]
            ]);
        }

        //
        // Limiting
        //
        if ($req->limit > 0) {
            $this->query->limit(
                $req->limitStart, $req->limit
            );
        }

        $this->performStringSearch($req);
        $this->performFilterSearch($req);

        $query = $this->query->getQuery($req->bool);

        try {
            $res->setQuery($query)->setResults(
                $this->search->search($req->indexes, $req->type, $query) ?: []
            );
        } catch (\Exception $ex) {
            // if this is an elastic exception, clean the error
            if (substr(get_class($ex), 0, 13) == 'Elasticsearch') {
                $error = json_decode($ex->getMessage());
                $error = $error ? $error->error->root_cause[0]->reason : $ex->getMessage();
                throw new \Exception($error, $ex->getCode(), $ex);
            }

            throw $ex;
        }
    }

    /**
     * Perform query searches
     */
    private function performStringSearch(SearchRequest $req)
    {
        // do nothing if no string
        if (strlen($req->string) < 1) {
            return;
        }

        switch($req->stringAlgo) {
            case SearchRequest::STRING_CUSTOM:
                $this->query->queryCustom($req->stringColumn, $req->string);
                break;
    
            case SearchRequest::STRING_WILDCARD:
                $this->query->queryWildcard($req->stringColumn, $req->string);
                break;
    
            case SearchRequest::STRING_WILDCARD_PLUS:
                $this->query->queryWildcardPlus($req->stringColumn, $req->string);
                break;
                
            case SearchRequest::STRING_FUZZY:
                $this->query->queryFuzzy($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_TERM:
                $this->query->queryTerm($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_PREFIX:
                $this->query->queryPrefix($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_MATCH:
                $this->query->queryMatch($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_MATCH_PHRASE:
                $this->query->queryMatchPhrase($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_MATCH_PHRASE_PREFIX:
                $this->query->queryMatchPhrasePrefix($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_MULTI_MATCH:
                $this->query->queryMultiMatch(
                    [ explode(',', $req->stringColumn) ], $req->string
                );
                break;

            case SearchRequest::STRING_QUERY_STRING:
                $this->query->queryString($req->stringColumn, $req->string);
                break;

            case SearchRequest::STRING_SIMILAR:
                $this->query->querySimilar($req->stringColumn, $req->string);
                break;
        }
    }

    /**
     * Perform filter searches
     */
    private function performFilterSearch(SearchRequest $searchRequest)
    {
        if (!$searchRequest->filters) {
            return;
        }

        $filters = str_getcsv($searchRequest->filters);

        foreach ($filters as $filter) {
            preg_match('/(?P<column>[A-Za-z0-9_\.]+)(?P<op>(?:=|[<>]=?))(?P<value>\w+)/', $filter, $matches);

            $column = $matches['column'] ?? null;
            $op     = $matches['op'] ?? null;
            $value  = $matches['value'] ?? null;

            if (!$column || !$op) {
                throw new \Exception("Invalid search filter: {$filter} - It must be: [COLUMN][OPERATOR][VALUE]");
            }

            if (in_array($op, ['='])) {
                $this->query->filterTerm($column, $value);
            } else if (in_array($op, ['>','<','>=','<='])) {
                $opConversion = [
                    '>=' => 'gte',
                    '<=' => 'lte',
                    '>' => 'gt',
                    '<' => 'lt'
                ];

                $this->query->filterRange($column, (int)$value, $opConversion[$op]);
            } else {
                throw new \Exception("Invalid operand provided: {$op}, please provide either: >, >=, <, <=, or =");
            }
        }
    }
}

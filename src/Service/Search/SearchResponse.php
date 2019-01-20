<?php

namespace App\Service\Search;

class SearchResponse
{
    /** @var array */
    public $query;
    /** @var SearchRequest */
    public $request;
    /** @var object */
    public $response = [
        'Pagination' => [],
        'Results'    => [],
        'SpeedMs'    => 0,
    ];
    
    public function __construct(SearchRequest $request)
    {
        $this->request = $request;
    }
    
    public function setQuery(array $query): SearchResponse
    {
        $this->query = $query;
        return $this;
    }
    
    /**
     * Set results from elastic search
     */
    public function setResults(array $results)
    {
        $this->response = (Object)$this->response;
        
        // no results? return now
        if (!$results) {
            return;
        }
    
        // add some stats
        $this->response->SpeedMs = $results['took'];
        $this->response->Results = $this->formatResults($results['hits']['hits']);
    
        // Pagination
        $totalResults = (int)$results['hits']['total'];
        $results = count($results['hits']['hits']);
        $pageTotal = $totalResults > 0 ? ceil($totalResults / $this->request->limit) : 0;
        $page = $this->request->page ?: 1;
        $page = $page >= 1 ? $page : 1;
        $pageNext = ($page + 1) <= $pageTotal ? ($page + 1) : null;
        $pagePrev = $page-1 > 0 ? $page-1 : null;
        $this->response->Pagination = [
            'Page'           => $results > 0 ? $page : 0,
            'PageTotal'      => $results > 0 ? $pageTotal : 0,
            'PageNext'       => $results > 0 ? $pageNext : null,
            'PagePrev'       => $results > 0 ? $pagePrev : null,
            'Results'        => $results,
            'ResultsPerPage' => $this->request->limit,
            'ResultsTotal'   => $totalResults,
        ];
    }
    
    /**
     * Format the search results
     */
    public function formatResults($hits)
    {
        $results = [];
        foreach ($hits as $hit) {
            $data            = $hit['_source'];
            $data['_']       = $hit['_index'];
            $data['_Score']  = $hit['_score'];
    
            if ($this->request->indexes != 'lore_finder') {
                $data['UrlType'] = explode('/', $data['Url'])[1];
            }
            
            $results[] = $data;
        }
        
        return $results;
    }
}

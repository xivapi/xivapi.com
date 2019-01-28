<?php

namespace App\Service\SearchElastic;

class ElasticQuery
{
    /** @var array */
    private $body = [];
    private $filters = [];
    private $suggestions = [];
    private $limit;
    private $sorting;

    /**
     * Types: https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-bool-query.html
     * - must
     * - must_not
     * - should
     * - filter
     */
    public function getQuery(string $type = 'must'): array
    {
        $response = [];
        
        if ($this->body || $this->filters) {
            $response['query'] = [
                'bool' => []
            ];
        }
        
        if ($this->limit) {
            $response['from'] = $this->limit[0];
            $response['size'] = $this->limit[1];
        }
        
        if ($this->sorting) {
            $response['sort'] = $this->sorting;
        }

        if ($this->body) {
            $response['query']['bool'][$type] = $this->body;
        }

        if ($this->filters) {
            $response['query']['bool']['filter'] = $this->filters;
        }

        return $response;
    }

    /**
     * Get JSON payload that is sent to ElasticSearch
     */
    public function getJson(string $type = 'must'): string
    {
        return json_encode($this->getQuery($type), JSON_PRETTY_PRINT);
    }

    //------------------------------------------------------------------------------------------------------------------

    public function limit(int $from, int $size): self
    {
        $this->limit = [$from, $size];
        return $this;
    }

    public function sort(array $sorting)
    {
        foreach ($sorting as $sort) {
            [$field, $order] = $sort;
            $this->sorting[] = [
                $field => $order
            ];
        }

        return $this;
    }

    public function minScore(float $score): self
    {
        $this->body['min_score'] = $score;
        return $this;
    }

    public function all(): self
    {
        $this->body['match_all'] = [
            "boost" => 1
        ];
        return $this;
    }
    
    public function nested(string $path, array $query): self
    {
        $nested = array_merge($query, [
            "path" => $path
        ]);
        
        $this->body[] = [
            "nested" => $nested
        ];

        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/search-suggesters.html
    public function addSuggestion(string $field, string $value): self
    {
        $this->suggestions['suggesty'] = [
            'text' => strtolower($value),
            'term' => [
                'field' => $field,
            ]
        ];

        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-ids-query.html
    public function queryIds(array $ids): self
    {
        $this->body[]['ids'] = [
            'values' => $ids
        ];

        return $this;
    }

    /**
     * This combines: WildCard Plus and Fuzzy
     */
    public function queryCustom(string $field, string $value): self
    {
        return $this
            ->queryWildcardPlus($field, $value)
            ->queryFuzzy($field, $value);
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-fuzzy-query.html
    public function queryFuzzy(string $field, string $value): self
    {
        $this->body[]['match'] = [
            $field => [
                'query' => strtolower($value),
                'fuzziness' => 'AUTO',
                'prefix_length' => 1,
                'max_expansions' => 10,
            ]
        ];

        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-term-query.html
    public function queryTerm(string $field, string $value): self
    {
        $this->body[]['term'] = [
            $field => strtolower($value)
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-wildcard-query.html
    public function queryWildcard(string $field, string $value): self
    {
        $this->body[]['wildcard'] = [
            $field => sprintf('*%s*', strtolower($value))
        ];
        return $this;
    }

    // this is similar to wildcard but will wildcard each word individually.
    public function queryWildcardPlus(string $field, string $value): self
    {
        foreach (explode(' ', $value) as $word) {
            $this->body[]['wildcard'] = [
                $field => sprintf('*%s*', strtolower($word))
            ];
        }

        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-prefix-query.html
    public function queryPrefix(string $field, string $value): self
    {
        $this->body[]['prefix'] = [
            $field => $value
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query.html
    public function queryMatch(string $field, string $value): self
    {
        $this->body[]['match'] = [
            $field => strtolower($value)
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase.html
    public function queryMatchPhrase(string $field, string $value): self
    {
        $this->body[]['match_phrase'] = [
            $field => strtolower($value)
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-match-query-phrase-prefix.html
    public function queryMatchPhrasePrefix(string $field, string $value): self
    {
        $this->body[]['match_phrase_prefix'] = [
            $field => strtolower($value)
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-multi-match-query.html
    public function queryMultiMatch(array $fields, string $value): self
    {
        $this->body[]['multi_match'] = [
            'query' => strtolower($value),
            'fields' => $fields
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-query-string-query.html
    public function queryString(string $field, string $query): self
    {
        $this->body[]['query_string'] = [
            'default_field' => $field,
            'query' => $query
        ];
        return $this;
    }

    // https://www.elastic.co/guide/en/elasticsearch/reference/current/query-dsl-mlt-query.html
    public function querySimilar($field, string $value): self
    {
        $this->body[]['more_like_this'] = [
            'fields' => [ $field ],
            'like'   => $value,
            'min_term_freq' => 1,
            'max_query_terms' => 12,
        ];

        return $this;
    }

    public function filterTerm(string $field, string $value): self
    {
        $this->filters[] = [
            'term' => [
                $field => strtolower($value),
            ]
        ];
        return $this;
    }

    public function filterRange(string $field, string $value, string $condition): self
    {
        $this->filters[] = [
            'range' => [
                $field => [ $condition => strtolower($value) ]
            ]
        ];
        return $this;
    }
}

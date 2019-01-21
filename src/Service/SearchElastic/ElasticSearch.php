<?php

namespace App\Service\SearchElastic;

use Elasticsearch\ClientBuilder;

class ElasticSearch
{
    const NUMBER_OF_SHARDS    = 1;
    const NUMBER_OF_REPLICAS  = 0;
    const MAX_RESULT_WINDOW   = 100000;
    const MAX_BULK_DOCUMENTS  = 250;
    const MAX_FIELDS          = 20000;

    /** @var \Elasticsearch\Client */
    private $client;

    public function __construct(string $ip = null, int $port = null)
    {
        $ip   = $ip ?: getenv('ELASTIC_IP');
        $port = $port ?: getenv('ELASTIC_PORT');
        
        if (!$ip || !$port) {
            throw new \Exception('No ElasticSearch IP or PORT configured in env file');
        }

        $hosts = sprintf("%s:%s", $ip, $port);
        $this->client = ClientBuilder::create()->setHosts([ $hosts ])->build();

        if (!$this->client) {
            throw new \Exception("Could not connect to ElasticSearch.");
        }
    }

    public function addIndex(string $index): void
    {
        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => ElasticMapping::ANALYSIS,
                    'number_of_shards'   => self::NUMBER_OF_SHARDS,
                    'number_of_replicas' => self::NUMBER_OF_REPLICAS,
                    'max_result_window'  => self::MAX_RESULT_WINDOW,
                    'index.mapping.total_fields.limit' => self::MAX_FIELDS,
                ],
                'mappings' => [
                    'search' => [
                        '_source' => [ 'enabled' => true ],
                        'dynamic' => true,
                        'dynamic_templates' => [
                            [
                                'strings' => [
                                    'match_mapping_type' => 'string',
                                    'mapping' => ElasticMapping::STRING
                                ],
                            ],[
                                'integers' => [
                                    'match_mapping_type' => 'long',
                                    'mapping' => ElasticMapping::INTEGER
                                ],
                            ],[
                                'booleans' => [
                                    'match_mapping_type' => 'boolean',
                                    'mapping' => ElasticMapping::BOOLEAN
                                ],
                            ],[
                                'texts' => [
                                    'match_mapping_type' => 'string',
                                    'mapping' => ElasticMapping::TEXT
                                ]
                            ]
                        ],
                    ],
                ]
            ]
        ]);
    }
    
    public function addIndexCompanion(string $index)
    {
        $this->client->indices()->create([
            'index' => $index,
            'body' => [
                'settings' => [
                    'analysis' => ElasticMapping::ANALYSIS,
                    'number_of_shards'   => self::NUMBER_OF_SHARDS,
                    'number_of_replicas' => self::NUMBER_OF_REPLICAS,
                    'max_result_window'  => self::MAX_RESULT_WINDOW,
                    'index.mapping.total_fields.limit' => self::MAX_FIELDS,
                ],
                'mappings' => [
                    // companion item price mapping
                    'companion' => [
                        '_source' => [ "enabled" => true ],
                        'properties' => [
                            "id"        => [ "type" => "text" ],
                            "server"    => [ "type" => "integer" ],
                            "item_id"   => [ "type" => "integer" ],
                            "prices"    => [
                                "type"  => "nested",
                                "properties" => [
                                    "id"                 => [ "type" => "long" ],
                                    "time"               => [ "type" => "integer" ],
                                    "is_crafted"         => [ "type" => "boolean" ],
                                    "is_hq"              => [ "type" => "boolean" ],
                                    "price_per_unit"     => [ "type" => "integer" ],
                                    "price_total"        => [ "type" => "integer" ],
                                    "quantity"           => [ "type" => "integer" ],
                                    "retainer_id"        => [ "type" => "integer" ],
                                    "craft_signature_id" => [ "type" => "integer" ],
                                    "town_id"            => [ "type" => "integer" ],
                                    "stain_id"           => [ "type" => "integer" ],
                                ]
                            ],
                            "history"   => [
                                "type"  => "nested",
                                "properties" => [
                                    "id"                 => [ "type" => "long" ],
                                    "time"               => [ "type" => "integer" ],
                                    "character_name"     => [ "type" => "integer" ],
                                    "is_hq"              => [ "type" => "boolean" ],
                                    "price_per_unit"     => [ "type" => "integer" ],
                                    "price_total"        => [ "type" => "integer" ],
                                    "quantity"           => [ "type" => "integer" ],
                                    "purchase_date"      => [ "type" => "integer" ],
                                ]
                            ]
                        ]
                    ],
                ]
            ]
        ]);
    }

    public function deleteIndex(string $index): void
    {
        if ($this->isIndex($index)) {
            $this->client->indices()->delete([
                'index' => $index
            ]);
        }
    }

    public function isIndex(string $index): bool
    {
        return $this->client->indices()->exists([
            'index' => $index
        ]);
    }

    public function addDocument(string $index, string $type, string $id, $document): void
    {
        $this->client->index([
            'index' => $index,
            'type'  => $type,
            'id'    => $id,
            'body'  => $document
        ]);
    }

    public function bulkDocuments(string $index, string $type, array $documents)
    {
        $params = [
            'body' => []
        ];
    
        foreach ($documents as $id => $doc) {
            $base = [
                'index' => [
                    '_index' => $index,
                    '_type'  => $type,
                    '_id'    => $id,
                ]
            ];
        
            $params['body'][] = $base;
            $params['body'][] = $doc;
        }
    
        return $this->client->bulk($params);
    }

    public function getDocument(string $index, string $type, string $id)
    {
        return $this->client->get([
            'index' => $index,
            'type'  => $type,
            'id'    => $id,
        ]);
    }

    public function deleteDocument(string $index, string $type, string $id): void
    {
        $this->client->indices()->delete([
            'index' => $index,
            'type' => $type,
            'id' => $id,
        ]);
    }

    public function getDocumentMapping(string $index)
    {
        return $this->client->indices()->getMapping([
            'index' => $index
        ]);
    }

    public function search(string $index, string $type, array $query)
    {
        return $this->client->search([
            'index' => $index,
            'type'  => $type,
            'body'  => $query
        ]);
    }

    public function count(string $index, string $type, array $query)
    {
        return $this->client->count([
            'index' => $index,
            'type'  => $type,
            'body'  => $query
        ]);
    }
}

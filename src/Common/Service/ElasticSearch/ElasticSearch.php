<?php

namespace App\Common\Service\ElasticSearch;

use Elasticsearch\ClientBuilder;

class ElasticSearch
{
    const NUMBER_OF_SHARDS = 1;
    const NUMBER_OF_REPLICAS = 0;
    const MAX_RESULT_WINDOW = 150000;
    const MAX_BULK_DOCUMENTS = 400;
    const MAX_FIELDS = 100000;

    /** @var \Elasticsearch\Client */
    private $client;

    public function __construct(string $environment)
    {
        if (getenv('ELASTIC_ENABLED') == 0) {
            return;
        }

        [$ip, $port, $login, $password] = explode(',', getenv($environment));

        if (!$ip || !$port) {
            throw new \Exception('No ElasticSearch IP or PORT configured in env file');
        }

        $hosts = [
            'host' => $ip,
            'port' => $port
        ];

        if (isset($login) && isset($password)) {
            $hosts['user'] = $login;
            $hosts['pass'] = $password;
        }
        $this->client = ClientBuilder::create()->setHosts(array($hosts))->build();

        if (!$this->client) {
            throw new \Exception("Could not connect to ElasticSearch.");
        }
    }

    public function putSettings($settings): void
    {
        $this->client->indices()->putSettings($settings);
    }

    /**
     * Add index for game data
     */
    public function addIndexGameData(string $index): void
    {
        $this->client->indices()->create([
            'index' => $index,
            'body'  => [
                'settings' => [
                    'analysis'                         => ElasticMapping::ANALYSIS,
                    'number_of_shards'                 => self::NUMBER_OF_SHARDS,
                    'number_of_replicas'               => self::NUMBER_OF_REPLICAS,
                    'max_result_window'                => self::MAX_RESULT_WINDOW,
                    'index.mapping.total_fields.limit' => self::MAX_FIELDS,
                ],
                'mappings' => [
                    'search' => [
                        '_source'           => ['enabled' => true],
                        'dynamic'           => true,
                        'dynamic_templates' => [
                            [
                                'names' => [
                                    'match_mapping_type' => 'string',
                                    'match'              => 'Name*',
                                    'mapping'            => ElasticMapping::ITEM_STRING
                                ],
                            ],[
                                'strings' => [
                                    'match_mapping_type' => 'string',
                                    'mapping'            => ElasticMapping::STRING
                                ],
                            ], [
                                'integers' => [
                                    'match_mapping_type' => 'long',
                                    'mapping'            => ElasticMapping::INTEGER
                                ],
                            ], [
                                'booleans' => [
                                    'match_mapping_type' => 'boolean',
                                    'mapping'            => ElasticMapping::BOOLEAN
                                ],
                            ], [
                                'texts' => [
                                    'match_mapping_type' => 'string',
                                    'mapping'            => ElasticMapping::TEXT
                                ]
                            ]
                        ],
                    ]
                ]
            ]
        ]);
    }

    /**
     * Add Companion Index
     */
    public function addIndexCompanion(string $index)
    {
        $this->client->indices()->create([
            'index' => $index,
            'body'  => [
                'settings' => [
                    'analysis'                         => ElasticMapping::ANALYSIS,
                    'number_of_shards'                 => self::NUMBER_OF_SHARDS,
                    'number_of_replicas'               => self::NUMBER_OF_REPLICAS,
                    'max_result_window'                => self::MAX_RESULT_WINDOW,
                    'index.mapping.total_fields.limit' => self::MAX_FIELDS,
                ],
                'mappings' => [
                    // companion item price mapping
                    'companion' => [
                        '_source'    => ["enabled" => true],
                        'properties' => [
                            "ID"      => ["type" => "text"],
                            "Server"  => ["type" => "integer"],
                            "ItemID"  => ["type" => "integer"],
                            "Prices"  => [
                                "type"       => "nested",
                                "properties" => [
                                    "ID"                   => ["type" => "text"],
                                    "Added"                => ["type" => "integer"],
                                    "IsCrafted"            => ["type" => "boolean"],
                                    "IsHq"                 => ["type" => "boolean"],
                                    "PricePerUnit"         => ["type" => "integer"],
                                    "PriceTotal"           => ["type" => "integer"],
                                    "Quantity"             => ["type" => "integer"],
                                    "RetainerID"           => ["type" => "text"],
                                    "RetainerName"         => ["type" => "text"],
                                    "CreatorSignatureID"   => ["type" => "text"],
                                    "CreatorSignatureName" => ["type" => "text"],
                                    "TownID"               => ["type" => "integer"],
                                    "StainID"              => ["type" => "integer"],
                                ]
                            ],
                            "History" => [
                                "type"       => "nested",
                                "properties" => [
                                    "ID"             => ["type" => "text"],
                                    "Added"          => ["type" => "integer"],
                                    "PurchaseDate"   => ["type" => "integer"],
                                    "PurchaseDateMs" => ["type" => "text"],
                                    "CharacterID"    => ["type" => "text"],
                                    "CharacterName"  => ["type" => "text"],
                                    "IsHq"           => ["type" => "boolean"],
                                    "PricePerUnit"   => ["type" => "integer"],
                                    "PriceTotal"     => ["type" => "integer"],
                                    "Quantity"       => ["type" => "integer"],
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

    public function getDocumentsBulk(string $index, string $type, array $keys)
    {
        return $this->client->mget([
            'index' => $index,
            'type'  => $type,
            "body"  => [
                'ids' => $keys
            ]
        ]);
    }

    public function deleteDocument(string $index, string $type, string $id): void
    {
        $this->client->delete([
            'index' => $index,
            'type'  => $type,
            'id'    => $id,
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

    public function hasIndex(string $index)
    {
        return $this->client->indices()->exists([
            'index' => $index
        ]);
    }
}

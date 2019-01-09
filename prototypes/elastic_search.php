<?php

require __DIR__.'/../vendor/autoload.php';

use App\Service\SearchElastic\ElasticSearch;
use App\Service\SearchElastic\ElasticMapping;
use App\Service\SearchElastic\ElasticQuery;

$elastic = new ElasticSearch();

// wipe index if it exists
$elastic->deleteIndex('test');

$settings = [
    'analysis' => ElasticMapping::ANALYSIS
];

// index mappings
$mapping = [
    'search' => [
        '_source' => [ 'enabled' => true ],
        'dynamic' => true,
        'dynamic_templates' => [
            [
                'strings' => [
                    'match_mapping_type' => 'string',
                    'mapping' => ElasticMapping::STRING
                ]
            ]
        ],
    ],
];

// create index
$elastic->addIndex('test', $mapping, $settings);

//////////////////////////////////////////////////////////////////

$data = '{
    "1675": {
        "ID": 1675,
        "ItemUICategory.Name": "Gladiator\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 50
        },
        "Name": "Curtana"
    },
    "1676": {
        "ID": 1676,
        "ItemUICategory.Name": "Gladiator\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 50
        },
        "Name": "Behemoth Knives"
    },
    "1677": {
        "ID": 1677,
        "ItemUICategory.Name": "Gladiator\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 70
        },
        "Name": "Zantetsuken"
    },
    "1678": {
        "ID": 1678,
        "ItemUICategory.Name": "Gladiator\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 70
        },
        "Name": "Test"
    },
    "1500": {
        "ID": 1500,
        "ItemUICategory.Name": "Other\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 90
        },
        "Col": "X",
        "Name": "Zantetsuken Test"
    },
    "12": {
        "ID": 12,
        "ItemUICategory.Name": "Other\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 90
        },
        "Col": "X",
        "Name": "two words"
    },
    "13": {
        "ID": 13,
        "ItemUICategory.Name": "Other\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 120
        },
        "Col": "X",
        "Name": "Omega Battleaxe"
    },
    "15": {
        "ID": 15,
        "ItemUICategory.Name": "Other\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 120
        },
        "Col": "X",
        "Name": "Omega FoxAxe"
    },
    "20": {
        "ID": 20,
        "ItemUICategory.Name": "Other\'s Arm",
        "Level": {
            "Equip": 50,
            "Item": 140
        },
        "Col": "X",
        "Name": "this is quite a long bit of text that will be searched"
        
    } 
}';

$elastic->bulkDocuments('test', 'search', json_decode($data, true));

// wait for eventual consistency
sleep(2);

$query = (new ElasticQuery())
    ->filterRange('Level.Item', 100, 'gte');
    //->queryCustom('Name', 'omage axe')
    //->addSuggestion('Name', 'omage axe');
    //->queryIds([1675,1676]);
    //->queryWildcard('Name', 'battle');

print_r(
    json_encode(
        $query->getQuery(),
        JSON_PRETTY_PRINT
    )
);

echo "\n\n\n";

$results = $elastic->search('test', 'search', $query);

print_r($results);



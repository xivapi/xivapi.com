<?php

namespace App\Common\Service\ElasticSearch;

/**
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping.html
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/mapping-types.html
 * https://www.elastic.co/guide/en/elasticsearch/reference/current/analysis.html
 */
class ElasticMapping
{
    const ANALYSIS = [
        'filter' => [
            "word_joiner" => [
                "type" => "word_delimiter",
                "catenate_all" => true
            ]
        ],
        'analyzer' => [
            // this allows: "mothermi" for: mother miounne but not "mother mi"
            'custom_string_search_concat' => [
                'type'      => 'custom',
                'tokenizer' => 'keyword',
                'filter'    => [
                    'lowercase',
                    'word_joiner'
                ]
            ],
            'custom_string_search_basic' => [
                'type'      => 'custom',
                'tokenizer' => 'keyword',
                'filter'    => [
                    'lowercase',
                ]
            ]
        ]
    ];

    const STRING = [
        'type' => 'text',
        'analyzer' => 'custom_string_search_basic'
    ];

    const ITEM_STRING = [
        'type' => 'text',
        'analyzer' => 'custom_string_search_basic',
        'fields' => [
            'raw' => [
                'type' => 'keyword',
                'ignore_above' => 256,
            ]
        ]
    ];

    const INTEGER = [
        'type' => 'long',
        'index' => true,
    ];

    const FLOAT = [
        'type' => 'float_range',
        'index' => true,
    ];

    const BOOLEAN = [
        'type' => 'boolean',
        'index' => true,
    ];

    const TEXT = [
        'type' => 'text',
        'index' => true,
        'fields' => [
            'raw' => [
                'type' => 'keyword',
                'ignore_above' => 256,
            ]
        ]
    ];

    const NESTED = [
        'type' => 'nested',
        'properties' => []
    ];

    /**
     * Detect the type of mapping the value should be
     */
    public static function detect($field, $value)
    {
        // fix objects
        if (is_object($value)) {
            print_r([
                "Error" => "Is object?",
                "Field" => $field,
                "Value" => $value
            ]);

            throw new \Exception("The provided value is an object, it should not. It should be either: numeric, a float, or a string");
        }

        if (is_numeric($value)) {
            return self::INTEGER;
        }

        if (is_float($value)) {
            return self::FLOAT;
        }

        if (strlen($value) > 200) {
            return self::TEXT;
        }

        return self::STRING;
    }
}

<?php

namespace App\Service\Data;

class DataHelper
{
    /**
     * Convert some columns into a more standard approach,
     * this is mainly for names.
     */
    const COLUMNS = [
        'Aetheryte' => [
            'Singular' => 'Name'
        ],
        'BNpcName' => [
            'Singular' => 'Name'
        ],
        'ENpcResident' => [
            'Singular' => 'Name'
        ],
        'Mount' => [
            'Singular' => 'Name'
        ],
        'Companion' => [
            'Singular' => 'Name'
        ],
        'Title' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Race' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Tribe' => [
            'Masculine' => 'Name',
            'Feminine' => 'NameFemale'
        ],
        'Quest' => [
            'Id' => 'TextFile',
        ],
        'EurekaAetherItem' => [
            'Singular' => 'Name'
        ],
        'GCRankGridaniaFemaleText' => [
            'Singular' => 'Name'
        ],
        'GCRankGridaniaMaleText' => [
            'Singular' => 'Name'
        ],
        'GCRankLimsaFemaleText' => [
            'Singular' => 'Name'
        ],
        'GCRankLimsaMaleText' => [
            'Singular' => 'Name'
        ],
        'GCRankUldahFemaleText' => [
            'Singular' => 'Name'
        ],
        'GCRankUldahMaleText' => [
            'Singular' => 'Name'
        ],
        'Ornament' => [
            'Singular' => 'Name'
        ]
    ];

    /**
     * Gets the real path to an image
     */
    public static function getImagePath($number, $hd = false)
    {
        $number = intval($number);
        $extended = (strlen($number) >= 6);

        if ($number == 0) {
            return null;
        }

        // create icon filename
        $icon = $extended ? str_pad($number, 5, "0", STR_PAD_LEFT) : '0' . str_pad($number, 5, "0", STR_PAD_LEFT);

        // create icon path
        $path = [];
        $path[] = $extended ? $icon[0] . $icon[1] . $icon[2] . '000' : '0' . $icon[1] . $icon[2] . '000';
        if ($hd) {
            $path[] = $icon . '_hr1';
        } else {
            $path[] = $icon;
        }

        // combine
        return '/i/' . implode('/', $path) . '.png';
    }

    /**
     * Fixes some column names so that everything is "Name" related
     */
    public static function getRealColumnNames(string $filename, array $columns)
    {
        $filename = explode('.', basename($filename))[0];

        // switch # to ID
        $columns[0] = $columns[0] == '#' ? 'ID' : $columns[0];

        // switch some column names
        $columns = self::getSimpleColumnName($columns);
        $columns = self::getReplacedName($filename, $columns);
        return $columns;
    }

    /**
     * Get a simplified column name
     */
    public static function getSimpleColumnName($columns)
    {
        $replace = [
            '[' => ' ',
            ']' => '',
            '{' => ' ',
            '}' => '',
            '<' => ' ',
            '>' => '',
            '(' => ' ',
            ')' => '',
        ];

        $columns = str_replace(array_keys($replace), $replace, $columns);

        if (is_array($columns)) {
            foreach ($columns as $i => $col) {
                $columns[$i] = ucwords($col);
            }
        } else {
            $columns = ucwords($columns);
        }

        return str_ireplace(' ', null, $columns);
    }

    /**
     * Get a replaced name
     */
    public static function getReplacedName($filename, $column)
    {
        $replace = DataHelper::COLUMNS;

        if (!isset($replace[$filename])) {
            return $column;
        }

        $replace = $replace[$filename];
        return str_replace(array_keys($replace), $replace, $column);
    }
}

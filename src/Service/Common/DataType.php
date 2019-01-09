<?php

namespace App\Service\Common;

/**
 * Handle various data types for content structures
 */
class DataType
{
    public static function ensureStrictDataTypes($array): array
    {
        $array = json_decode(json_encode($array), true);
        
        foreach ($array as $i => $value) {
            if (is_array($value)) {
                $array[$i] = self::ensureStrictDataTypes($value);
            } else {
                if (count(explode('.', $value)) > 1) {
                    $array[$i] = (string)trim($value);
                } else if (is_numeric($value)) {
                    $array[$i] = strlen($value) >= 12 ? (string)trim($value) : (int)intval(trim($value));
                } else if ($value === true || $value === false) {
                    $array[$i] = (bool)$value;
                }
            }
        }

        return $array;
    }
}

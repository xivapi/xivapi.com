<?php

namespace App\Common\Utils;

class Arrays
{
    /**
     * Extra columns from an array
     */
    public static function extractColumns(array $array, array $columns): array
    {
        $columns = self::detectRangeColumns($columns);

        // empty?
        if (!$columns) {
            return $array;
        }

        $newData = [];
        foreach ($columns as $col) {
            $newData[$col] = self::getArrayValueFromDotNotation($array, $col);
        }

        foreach ($newData as $index => $value) {
            $dotCount = substr_count($index, '.');

            if ($dotCount > 10) {
                throw new \Exception("What possible data is in 10 nested arrays?");
            }

            if ($dotCount > 0) {
                self::handleDotNotationToArray($newData, $index, $value);
                unset($newData[$index]);
            }
        }

        return $newData;
    }
    
    /**
     * Extra columns
     */
    public static function extractColumnsCount(array $array, $columns): array
    {
        foreach ($columns as $i => $col) {
            if (stripos($col, '.*.') !== false) {
                $col = explode('.*.', $col);
                
                $columnValue = self::getArrayValueFromDotNotation($array, $col[0]);
                $total = is_array($columnValue) ? count($columnValue)-1 : null;
            
                if ($total === null) {
                    throw new \Exception("The column {$col[0]} is not an array.");
                }

                if ($total < 0) {
                    $columns[$i] = "{$col[0]}";
                    continue;
                }
            
                $columns[$i] = "{$col[0]}.*{$total}.${col[1]}";
            }
        }
        
        return $columns;
    }

    /**
     * Allows _* for all languages
     */
    public static function extractMultiLanguageColumns(array $columns): array
    {
        foreach ($columns as $i => $col) {
            if (stripos($col, '_*') !== false) {
                unset($columns[$i]);

                foreach (Language::LANGUAGES_ACTIVE as $lang) {
                    $columns[] = str_ireplace('_*', "_{$lang}", $col);
                }
            }
        }

        return $columns;
    }

    /**
     * Convert dot notations into arrays
     */
    public static function handleDotNotationToArray(array &$array, string $key, $value)
    {
        if (is_null($key)) {
            return $array = $value;
        }

        $keys = explode('.', $key);

        while (count($keys) > 1) {
            $key = array_shift($keys);

            // If the key doesn't exist at this depth, we will just create an empty array
            // to hold the next value, allowing us to create the arrays to hold final
            // values at the correct depth. Then we'll keep digging into the array.
            if (! isset($array[$key]) || ! is_array($array[$key])) {
                $array[$key] = [];
            }

            $array = &$array[$key];
        }

        $array[array_shift($keys)] = $value;
        return $array;
    }

    /**
     * look for x.* columns and pre-populate arrays
     */
    public static function detectRangeColumns(array $columns): array
    {
        // reformat some keys
        foreach ($columns as $i => $column) {
            $column = explode('.', $column);

            $countColumn = false;
            foreach ($column as $j => $col) {
                if (substr($col, 0, 1) === '*') {
                    // remove this column as it will be merged later
                    unset($columns[$i]);

                    // grab column count
                    $countColumn = (int)substr($col, 1);
                    break;
                }
            }

            // Append all count columns
            foreach (range(0, $countColumn) as $r) {
                $columns[] = implode(
                    '.', str_ireplace("*{$countColumn}", $r, $column)
                );
            }
        }

        return $columns;
    }

    /**
     * Get an array value via dot notation
     */
    public static function getArrayValueFromDotNotation(array $array, string $key, $default = null)
    {
        $value = $default;
        if (is_array($array) && array_key_exists($key, $array)) {
            $value = $array[$key];
        } else if (is_object($array) && property_exists($array, $key)) {
            $value = $array->$key;
        } else {
            $segments = explode('.', $key);

            foreach ($segments as $segment) {
                if (is_array($array) && array_key_exists($segment, $array)) {
                    $value = $array = $array[$segment];
                } else if (is_object($array) && property_exists($array, $segment)) {
                    $value = $array = $array->$segment;
                } else {
                    $value = $default;
                    break;
                }
            }
        }

        return $value;
    }

    /**
     * Flattens an array into dot notation
     */
    public static function flattenArray($array, $prepend = '')
    {
        $results = [];
        foreach ($array as $key => $value) {
            if (is_array($value) && ! empty($value)) {
                $results = array_merge($results, self::flattenArray($value, $prepend.$key.'.'));
            } else {
                $results[$prepend.$key] = $value;
            }
        }
        return $results;
    }
    
    /**
     * reverse of flatten array
     */
    public static function unflattenArray($array)
    {
        foreach ($array as $key => $value) {
            self::handleDotNotationToArray($array, $key, $value);
        }
        
        return $array;
    }

    /**
     * Describes an array
     * - values that are an array will become []
     * - Values not array will be "best guess"
     */
    public static function describeArray($array)
    {
        foreach ($array as $i => $a) {
            if (is_array($a) && !is_numeric($i)) {
                if (self::isAssociateArray($a)) {
                    $array[$i] = self::describeArray($a);
                } else {
                    $array[$i] = "array";
                }
            } else {
                if ($a === true || $a === false) {
                    $array[$i] = "boolean";
                } else if (is_numeric($a)) {
                    $array[$i] = "int";
                } else if (is_float($a)) {
                    $array[$i] = "int";
                } else if (is_string($a)) {
                    $array[$i] = "string";
                } else if (is_object($a)) {
                    $array[$i] = "object";
                } else {
                    if (isset($array["{$i}Target"])) {
                        $array[$i] = 'Object<'. $array["{$i}Target"] ?: '?' .'>';
                    } else {
                        $array[$i] = "[?] {$a}";
                    }
                }
            }
        }

        return $array;
    }

    /**
     * Returns true OR false if the array is Associate or not (Numeric)
     */
    public static function isAssociateArray(array $arr)
    {
        if (array() === $arr) return false;
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    /**
     * Sort a multi-dimensional array by its keys
     */
    public static function sortArrayByKey(array $array)
    {
        foreach ($array as $i => $value) {
            if (is_array($value)) {
                $array[$i] = self::sortArrayByKey($value);
            }
        }

        ksort($array);
        return $array;
    }
    
    /**
     * Sort a multi-dimensional object by its keys
     */
    public static function sortObjectByKey($object)
    {
        if (!$object || (!is_object($object) && !is_array($object))) {
            return;
        }
    
        foreach ($object as $i => $value) {
            if (is_object($value)) {
                self::sortObjectByKey($value);
            }
        }
    
        $ksort = new \ArrayObject($object);
        $ksort->ksort();
    }
    
    /**
     * Provides a minified version of  specific piece of content
     */
    public static function minification($content)
    {
        if (!$content) {
            return $content;
        }
        
        $content = json_decode(json_encode($content), true);
        
        unset($content['GameContentLinks']);
        foreach ($content as $field => $value) {
            if (is_array($value)) {
                $value = $value['ID'] ?? $value;
            }
            
            if (is_array($value)) {
                foreach ($value as $i => $val) {
                    $value[$i] = $val['ID'] ?? null;
                }
            }
            
            $content[$field] = $value ?: null;
        }
    
        $content = json_decode(json_encode($content));
        
        return $content;
    }

    /**
     * Convert an array to snake case
     */
    public static function snakeCase(&$array)
    {
        foreach (array_keys($array) as $key) {
            # Working with references here to avoid copying the value,
            # since you said your data is quite large.
            $value = &$array[$key];
            unset($array[$key]);

            # This is what you actually want to do with your keys:
            #  - remove exclamation marks at the front
            #  - camelCase to snake_case
            $fixes = [
                'PvPTeam' => 'PvpTeam',
                'ID' => 'Id'
            ];
            
            $transformedKey = strtolower(preg_replace('/([a-z])([A-Z])/', '$1_$2', ltrim(str_ireplace(array_keys($fixes), $fixes, $key), '!')));

            # Work recursively
            if (is_array($value)) {
                self::snakeCase($value);
            }
            # Store with new key
            $array[$transformedKey] = $value;

            # Do not forget to unset references!
            unset($value);
        }
    }

    /**
     * Remove all keys from an array
     */
    public static function removeKeys(&$array)
    {
        $array = array_values($array);
        for ($i = 0, $n = count($array); $i < $n; $i++) {
            $element = $array[$i];

            if (is_array($element)) {
                $array[$i] = self::removeKeys($element);
            }
        }

        return $array;
    }

    /**
     * Write a repository response to a CSV
     */
    public static function repositoryToCsv($repo, $filename)
    {
        $arr = [];
        foreach ($repo->findAll() as $obj) {
            if (empty($arr)) {
                $arr[] = array_keys($obj->toArray());
            }

            $arr[] = array_values($obj->toArray());
        }

        // write to file
        $fp = fopen($filename, 'w');
        foreach ($arr as $fields) {
            fputcsv($fp, $fields);
        }

        fclose($fp);
    }
    
    /**
     * Sort an array via its sub key
     */
    public static function sortBySubKey(&$array, $subkey, $sort_ascending = false)
    {
        if ($array) {
            if (count($array)) {
                $temp_array[key($array)] = array_shift($array);
            }
            
            foreach($array as $key => $val) {
                $offset = 0;
                $found = false;
                foreach($temp_array as $tmp_key => $tmp_val) {
                    if(!$found and strtolower($val[$subkey]) > strtolower($tmp_val[$subkey])) {
                        $temp_array = array_merge((array)array_slice($temp_array,0,$offset),
                            [$key => $val],
                            array_slice($temp_array,$offset)
                        );
                        $found = true;
                    }
                    
                    $offset++;
                }
                
                if(!$found) {
                    $temp_array = array_merge($temp_array, [$key => $val]);
                }
            }
            
            if ($sort_ascending) {
                $array = array_reverse($temp_array);
            } else {
                $array = $temp_array;
            }
            
            $array = array_values($array);
        }
    }
    
    public static function ensureStrictDataTypes($array): array
    {
        if (is_object($array)) {
            $array = json_decode(json_encode($array), true);
        }
        
        foreach ($array as $i => $value) {
            if (is_array($value)) {
                $array[$i] = self::ensureStrictDataTypes($value);
            } else {
                if (count(explode('.', $value)) > 1) {
                    $array[$i] = (string)trim($value);
                } else if (ctype_digit($value) && is_numeric($value)) {
                    $array[$i] = strlen($value) >= 12 ? (string)trim($value) : (int)intval(trim($value));
                } else if ($value === true || $value === false) {
                    $array[$i] = (bool)$value;
                }
            }
        }
        
        return $array;
    }
}

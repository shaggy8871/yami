<?php declare(strict_types=1);

namespace Yami\Config;

class Utils
{

    /**
     * A better version of array_merge_recursive that doesn't duplicate values
     * 
     * @param array the first array
     * @param array the second array
     * 
     * @return array
     */
    public static function mergeRecursively(array &$arr1, array &$arr2): array
    {
        $merged = $arr1;

        foreach ($arr2 as $key => &$value) {
            if (is_array($value) && isset($merged[$key]) && is_array($merged[$key])) {
                $merged[$key] = static::mergeRecursively($merged[$key], $value);
            } else {
                $merged[$key] = $value;
            }
        }

        return $merged;
    }

    /**
     * Recursively remove empty nodes
     * 
     * @param array the array
     * 
     * @return array
     */
    public static function removeEmpty(array $i): array
    {
        foreach ($i as &$value) {
            if (is_array($value)) {
                $value = static::removeEmpty($value);
            }
        }
        return array_filter($i, function($v) {
            return $v !== [];
        });
    }

    /**
     * Mask all values in the array
     * 
     * @param array the array
     */
    public static function maskValues(array $i): array
    {
        foreach ($i as &$value) {
            if (is_array($value)) {
                $value = static::maskValues($value);
            }
        }
        return array_map(function($v) {
            if (is_scalar($v)) {
                return '(masked)';
            }
            return $v;
        }, $i);
    }

}
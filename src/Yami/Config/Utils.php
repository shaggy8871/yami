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

}
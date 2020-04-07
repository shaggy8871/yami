<?php declare(strict_types=1);

namespace Yami\Secrets;

class Utils
{

    /**
     * Transforms a secrets manager key to a valid environment-compatible name.
     * For example:
     *    /Api/Production/S3/access_key_id
     * becomes the following:
     *    Api_Production_S3_AccessKeyId
     * 
     * @param string the key
     * 
     * @return string
     */
    public static function keyToEnv(string $key): string
    {
        return trim(preg_replace('/[\/.-]+/', '_', preg_replace_callback('/\/([a-z0-9_]+)$/', function($m) {
            $parts = explode('_', $m[1]);
            return '/' . implode('', array_map(function($e) {
                return ucfirst($e);
            }, $parts));
        }, $key)), '_');
    }

}
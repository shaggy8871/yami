<?php declare(strict_types=1);

namespace Yami\Yaml;

use stdClass;

class AdapterFactory
{

    const DEFAULT_ADAPTER = 'file';

    /**
     * Load the YAML file from disk
     * 
     * @return string
     */
    public static function loadFrom(stdClass $config, stdClass $environment): AdapterInterface
    {
        $adapter = $environment->adapter ?? self::DEFAULT_ADAPTER;

        switch($adapter) {
            case 'stream':
                return new Adapters\Stream($config, $environment);
            case 'file':
            default:
                return new Adapters\File($config, $environment);
        }
    }

}
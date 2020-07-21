<?php declare(strict_types=1);

namespace Yami\Yaml;

use stdClass;

class YamlAdapterFactory
{

    const DEFAULT_ADAPTER = 'file';

    public static $knownAdapters = [
        's3'     => Adapters\S3::class,
        'stream' => Adapters\Stream::class,
        'file'   => Adapters\File::class,
    ];

    /**
     * Load the YAML file from disk
     * 
     * @return string
     */
    public static function loadFrom(stdClass $config, stdClass $environment): YamlAdapterInterface
    {
        $yamlAdapter = $environment->yaml->adapter ?? self::DEFAULT_ADAPTER;

        if (isset(static::$knownAdapters[$yamlAdapter])) {
            $class = static::$knownAdapters[$yamlAdapter];
        } else
        if (class_exists($yamlAdapter)) {
            $class = $yamlAdapter;
        }

        if (isset($class)) {
            $instance = new $class($config, $environment);
            if ($instance instanceof YamlAdapterInterface) {
                return $instance;
            }
        }

        throw new \Exception(sprintf('Unable to find or instantiate YAML adapter class %s.', $yamlAdapter));
    }

}
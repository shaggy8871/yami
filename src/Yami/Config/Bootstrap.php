<?php declare(strict_types=1);

namespace Yami\Config;

use Console\Args;
use DateTime;

class Bootstrap
{

    const DEFAULT_ENV = 'default';

    /**
     * @var array
     */
    protected static $config;

    /**
     * @var array
     */
    protected static $defaultConfig = [
        'load' => [
            'asObject'              => false,
            'asYamlMap'             => false,
        ],
        'save' => [
            'indentation'           => 2,
            'inlineFromLevel'       => 10,
            'asObject'              => false,
            'asYamlMap'             => false,
            'asMultilineLiteral'    => false,
            'base64BinaryData'      => false,
            'nullAsTilde'           => false,
        ],
        'environments' => [
            'default' => [
                'yamlFile' => 'default.yaml',
                'path' => './migrations',
            ],
        ],
    ];

    /**
     * Returns a merged set of configuration settings
     */
    public static function getConfig()
    {
        if (static::$config != null) {
            return static::$config;
        }

        if (file_exists('config.php')) {
            $customConfig = include('config.php');
        } else {
            echo "Cannot find config.php in root. Run `vendor/bin/yami config` to create it.\nReverting to defaults...\n";
        }

        static::$config = json_decode(json_encode(static::mergeRecursively(static::$defaultConfig, $customConfig)));

        return static::$config;
    }

    /**
     * For verification migrations, we create a copy of the YAML file and operate on that
     */
    public static function createMockYaml(Args $args): string
    {
        $config = static::getConfig();
        $environment = $args->env ?? static::DEFAULT_ENV;

        $originalYaml = static::$config->environments->$environment->yamlFile;
        $mockFilename = str_replace('.yaml', '_' . (new DateTime())->format('YmdHis') . '.mock.yaml', $originalYaml);

        file_put_contents($mockFilename, file_get_contents($originalYaml));

        static::$config->environments->$environment->yamlFile = $mockFilename;

        return $mockFilename;
    }

    /**
     * For verification migrations, delete the mock file
     */
    public static function deleteMockYaml(Args $args): void
    {
        $config = static::getConfig();
        $environment = $args->env ?? static::DEFAULT_ENV;

        unlink(static::$config->environments->$environment->yamlFile);
    }

    /**
     * Determines the environment from console arguments
     */
    public static function getEnvironment(Args $args)
    {
        $config = static::getConfig();
        $environment = $args->env ?? static::DEFAULT_ENV;

        if (!isset($config->environments->$environment)) {
            throw new \Exception(sprintf('Unable to find environment "%s" in configuration.', $environment));
        }

        $config->environments->$environment->name = $environment;

        return $config->environments->$environment;
    }

    /**
     * A better version of array_merge_recursive that doesn't duplicate values
     */
    protected static function mergeRecursively(array &$arr1, array &$arr2): array
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
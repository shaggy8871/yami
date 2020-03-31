<?php declare(strict_types=1);

namespace Yami\Config;

class Bootstrap
{

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
        'yamlFile' => 'default.yaml',
        'path' => './migrations',
    ];

    public static function getConfig()
    {
        if (file_exists('config.php')) {
            $config = include('config.php');
        } else {
            echo "Cannot find config.php in root. Run `vendor/bin/yami config` to create it.\nReverting to defaults...\n";
        }

        return json_decode(json_encode(array_merge(static::$defaultConfig, $config)));
    }

}
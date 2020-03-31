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
        ]
    ];

    public static function getConfig()
    {
        return json_decode(json_encode(array_merge(static::$defaultConfig, [
            'yamlFile' => 'test.yaml'
        ])));
    }

}
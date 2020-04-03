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
            'maskValues'            => false,
            'removeEmptyNodes'      => true,
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
     * 
     * @param Args the console arguments
     * 
     * @return stdClass
     */
    public static function getConfig(Args $args): \stdClass
    {
        if (static::$config != null) {
            return static::$config;
        }

        if (file_exists('./config.php')) {
            $customConfig = include('./config.php');
        } else {
            echo "Cannot find config.php in root. Run `vendor/bin/yami config` to create it.\nReverting to defaults...\n";
        }

        static::$config = json_decode(json_encode(Utils::mergeRecursively(static::$defaultConfig, $customConfig)));

        $environment = static::validateEnvArgument(static::$config, $args);

        // Merge in environment specific load and save settings
        if (isset(static::$config->environments->$environment->load) && is_array(static::$config->environments->$environment->load)) {
            static::$config->environments->$environment->load = json_decode(json_encode(array_merge((array) static::$config, static::$config->environments->$environment->load)));
        }
        if (isset(static::$config->environments->$environment->save) && is_array(static::$config->environments->$environment->save)) {
            static::$config->environments->$environment->save = json_decode(json_encode(array_merge((array) static::$config, static::$config->environments->$environment->save)));
        }

        return static::$config;
    }

    /**
     * For verification migrations, we create a copy of the YAML file and operate on that
     * 
     * @param Args the console arguments
     * @param string optional YAML to mock with
     * 
     * @return array
     */
    public static function createMockYaml(Args $args, ?string $yaml = null): string
    {
        $config = static::getConfig($args);
        $environment = static::validateEnvArgument($config, $args);

        $originalYaml = static::$config->environments->$environment->yamlFile;
        $mockFilename = str_replace('.yaml', '_' . (new DateTime())->format('YmdHis') . '.mock.yaml', $originalYaml);

        file_put_contents($mockFilename, $yaml ?? file_get_contents($originalYaml));

        static::$config->environments->$environment->yamlFile = $mockFilename;

        return $mockFilename;
    }

    /**
     * For verification migrations, delete the mock file
     * 
     * @param Args the console arguments
     * 
     * @return array
     */
    public static function deleteMockYaml(Args $args): void
    {
        $config = static::getConfig($args);
        $environment = static::validateEnvArgument($config, $args);

        unlink(static::$config->environments->$environment->yamlFile);
    }

    /**
     * Determines the environment from console arguments
     * 
     * @param Args the console arguments
     * 
     * @return stdClass
     */
    public static function getEnvironment(Args $args): \stdClass
    {
        $config = static::getConfig($args);
        $environment = static::validateEnvArgument($config, $args);

        $config->environments->$environment->name = $environment;

        return $config->environments->$environment;
    }

    /**
     * For test purposes, seed the config before querying
     * 
     * @param array the replacement config
     * 
     * @return void
     */
    public static function seedConfig(array $customConfig): void
    {
        static::$config = json_decode(json_encode(Utils::mergeRecursively(static::$defaultConfig, $customConfig)));
    }

    /**
     * Validate the environment argument against the config
     * 
     * @param stdClass the config object
     * @param Args the console argument
     * 
     * @throws Exception
     * @returns string
     */
    private static function validateEnvArgument(\stdClass $config, Args $args): string
    {
        $environment = $args->env ?? static::DEFAULT_ENV;

        if (!isset($config->environments->$environment)) {
            throw new \Exception(sprintf('Unable to find environment "%s" in configuration.', $environment));
        }

        return $environment;
    }

}
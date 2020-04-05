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
     * @var string
     */
    protected static $configId;

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

        if ($args->config) {
            if (!file_exists($args->config)) {
                throw new \Exception(sprintf('Unable to find config file %s.', $args->config));
            }
        }

        $configFile = $args->config ? $args->config : './config.php';
        if (file_exists($configFile)) {
            $customConfig = include($configFile);
        } else {
            throw new \Exception('Cannot find config file %s. Run `vendor/bin/yami config` to create one.');
        }

        // Save config file id
        static::setConfigId($configFile);

        static::$config = json_decode(json_encode(Utils::mergeRecursively(static::$defaultConfig, $customConfig)));

        $environment = static::validateEnvArgument(static::$config, $args);

        // Merge in environment specific load and save settings
        if (isset(static::$config->environments->$environment->load) && is_object(static::$config->environments->$environment->load)) {
            static::$config->load = json_decode(json_encode(array_merge((array) static::$config->load, (array) static::$config->environments->$environment->load)));
        }
        if (isset(static::$config->environments->$environment->save) && is_object(static::$config->environments->$environment->save)) {
            static::$config->save = json_decode(json_encode(array_merge((array) static::$config->save, (array) static::$config->environments->$environment->save)));
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
        $mockFilename = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.mock.$1', $originalYaml);

        file_put_contents($mockFilename, $yaml ?? trim(file_get_contents($originalYaml)));

        static::$config->environments->$environment->yamlFile = $mockFilename;

        return $originalYaml;
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
     * Sets the config file identifier which is used for history tracking
     * 
     * @param string the config file name and path
     * 
     * @return void
     */
    public static function setConfigId(string $configFile): void
    {
        static::$configId = trim(str_replace('_php', '', preg_replace('/[^\w]/', '_', $configFile)), '_');
    }

    /**
     * Gets the config file identifier
     * 
     * @return string
     */
    public static function getConfigId(): string
    {
        return static::$configId;
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
        $environment = $args->env ?? '';

        if ($environment != '' && !preg_match('/[a-z0-9_]+/', $environment)) {
            throw new \Exception(sprintf('Environment "%s" is not a valid name. Please use only a-z0-9 and _ characters.', $environment));
        }
        if ($environment != '' && isset($config->environments->$environment)) {
            return $environment;
        }
        // Fallback to first
        if ($environment == '' && count((array) $config->environments)) {
            return array_keys((array) $config->environments)[0];
        }

        throw new \Exception(sprintf('Unable to find environment "%s" in configuration.', $environment));
    }

}
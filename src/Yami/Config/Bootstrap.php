<?php declare(strict_types=1);

namespace Yami\Config;

use Console\Args;
use DateTime;

class Bootstrap
{

    const DEFAULT_ENV = 'default';

    /**
     * @var self
     */
    protected static $instance = null;

    /**
     * @var Args
     */
    protected $args;

    /**
     * @var array
     */
    protected $config;

    /**
     * @var string
     */
    protected $configId;

    /**
     * @var array
     */
    protected $defaultConfig = [
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
        'environments'              => [],
        'historyFileName'           => './history.log',
    ];

    /**
     * Constructor
     */
    protected function __construct(Args $args)
    {
        $this->args = $args;
    }

    /**
     * Returns a singleton instance of the Bootstrap class
     * 
     * @param Args the console arguments
     * 
     * @return self
     */
    public static function getInstance(?Args $args): self
    {
        if (!(static::$instance instanceof self)) {
            static::$instance = new self($args);
        }
        if ($args) {
            static::$instance->setArgs($args);
        }
        return static::$instance;
    }

    /**
     * Overwrite the args with a new set
     * 
     * @param Args the console arguments
     * 
     * @return void
     */
    public function setArgs(Args $args): void
    {
        $this->args = $args;
    }

    /**
     * Clears the config to force it to be reloaded
     * 
     * @return void
     */
    public function clearConfig(): void
    {
        $this->config = null;
    }

    /**
     * Returns a merged set of configuration settings
     *
     * @return stdClass
     */
    public function getConfig(): \stdClass
    {
        if ($this->config != null) {
            return $this->config;
        }

        $args = $this->args;

        if ($args->config && realpath($args->config)) {
            // Normalise config file path
            $args->config = '.' . str_replace(getcwd(), '', realpath($args->config));
            if (!file_exists($args->config)) {
                throw new \Exception(sprintf('Unable to find config file %s.', $args->config));
            }
        }

        $configFile = $args->config ? $args->config : './config.php';
        if (file_exists($configFile)) {
            $customConfig = include($configFile);
        } else {
            throw new \Exception(sprintf('Cannot find config file "%s". Run `vendor/bin/yami config` to create one.', $configFile));
        }

        // Save config file id
        $this->setConfigId($configFile);

        $this->config = json_decode(json_encode(Utils::mergeRecursively($this->defaultConfig, $customConfig)));

        $environment = $this->validateEnvArgument($this->config);

        // Merge in environment specific load, save and historyFile settings
        if (isset($this->config->environments->$environment->load) && is_object($this->config->environments->$environment->load)) {
            $this->config->load = json_decode(json_encode(array_merge((array) $this->config->load, (array) $this->config->environments->$environment->load)));
        }
        if (isset($this->config->environments->$environment->save) && is_object($this->config->environments->$environment->save)) {
            $this->config->save = json_decode(json_encode(array_merge((array) $this->config->save, (array) $this->config->environments->$environment->save)));
        }
        if (isset($this->config->environments->$environment->historyFileName)) {
            $this->config->historyFileName = $this->config->environments->$environment->historyFileName;
        }

        return $this->config;
    }

    /**
     * For verification migrations, we create a copy of the YAML file and operate on that
     *
     * @param string optional YAML to mock with
     *
     * @return array
     */
    public function createMockYaml(?string $yaml = null): string
    {
        $config = $this->getConfig();
        $environment = $this->validateEnvArgument($config);

        $originalYaml = $this->config->environments->$environment->yamlFile;
        $mockFilename = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.mock.$1', $originalYaml);

        file_put_contents($mockFilename, $yaml ?? trim(file_get_contents($originalYaml)));

        $this->config->environments->$environment->yamlFile = $mockFilename;

        return $originalYaml;
    }

    /**
     * For verification migrations, delete the mock file
     *
     * @return array
     */
    public function deleteMockYaml(): void
    {
        $config = $this->getConfig();
        $environment = $this->validateEnvArgument($config);

        unlink($this->config->environments->$environment->yamlFile);
    }

    /**
     * Determines the environment from console arguments
     *
     * @return stdClass
     */
    public function getEnvironment(): \stdClass
    {
        $config = $this->getConfig();
        $environment = $this->validateEnvArgument($config);

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
    public function setConfigId(string $configFile): void
    {
        $this->configId = trim(str_replace('_php', '', preg_replace('/[^\w]/', '_', $configFile)), '_');
    }

    /**
     * Gets the config file identifier
     *
     * @return string
     */
    public function getConfigId(): string
    {
        return $this->configId;
    }

    /**
     * For test purposes, seed the config before querying
     *
     * @param array the replacement config
     *
     * @return void
     */
    public function seedConfig(array $customConfig): void
    {
        $this->config = json_decode(json_encode(Utils::mergeRecursively($this->defaultConfig, $customConfig)));
    }

    /**
     * Validate the environment argument against the config
     *
     * @param stdClass the config object
     *
     * @throws Exception
     * @returns string
     */
    private function validateEnvArgument(\stdClass $config): string
    {
        $environment = $this->args->env ?? '';

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

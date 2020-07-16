<?php declare(strict_types=1);

namespace Yami\Migration;

use Console\Args;
use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use Yami\Config\{Bootstrap, Utils};
use Yami\Yaml\{YamlAdapterFactory, YamlAdapterInterface};
use Yami\Secrets\{SecretsManagerFactory, Utils as SecretsUtil};
use stdClass;

abstract class AbstractMigration
{

    const ACTION_MIGRATE  = 'migrate';
    const ACTION_ROLLBACK = 'rollback';

    /**
     * @var stdClass
     */
    protected $migration;

    /**
     * @var Args
     */
    protected $args;

    /**
     * @var stdClass
     */
    protected $config;

    /**
     * @var array
     */
    protected $environment;

    /**
     * @var YamlAdapterInterface
     */
    protected $adapter;

    /**
     * @var array
     */
    protected $yaml;

    /**
     * @var Yami\Migration\Node
     */
    protected $activeNode;

    public function __construct(stdClass $migration, Args $args, Bootstrap $bootstrap, YamlAdapterInterface $adapter)
    {
        $this->migration = $migration;
        $this->args = $args;
        $this->config = $bootstrap->getConfig();
        $this->environment = $bootstrap->getEnvironment();
        $this->adapter = $adapter;
    }

    /**
     * Run the migration or rollback
     * 
     * @param string the action to run
     */
    public function run(string $action): void
    {
        switch ($action) {
            case self::ACTION_MIGRATE:
                if (method_exists($this, 'up')) {
                    $this->up();
                } else {
                    throw new \Exception(sprintf('Unable to find up() method in migration %s', $this->migration->uniqueId));
                }
                break;
            case self::ACTION_ROLLBACK:
                if (method_exists($this, 'down')) {
                    $this->down();
                } else {
                    throw new \Exception(sprintf('Unable to find down() method in migration %s', $this->migration->uniqueId));
                }
                break;
        }
    }

    /**
     * Set the current state of the YAML or loads it
     * 
     * @param array|null The YAML array
     * 
     * @return void
     */
    public function setState(?array $yaml = null): void
    {
        $this->yaml = $yaml ?? $this->adapter->load();
    }

    /**
     * Gets the current state of the YAML array
     * 
     * @return array
     */
    public function getState(): array
    {
        return $this->yaml;
    }

    /**
     * Finds and returns a tree node
     * 
     * @param string selector
     * 
     * @return Yami\Migration\Node
     */
    public function get(string $selector): Node
    {
        // Save last changes (just in case)
        if ($this->activeNode instanceof Node) {
            $this->syncNode(true);
        }

        $this->activeNode = $this->findNode($selector); // don't catch exception

        return $this->activeNode;
    }

    /**
     * Checks whether the specified key exists
     * 
     * @param string selector
     * 
     * @return bool
     */
    public function exists(string $selector): bool
    {
        try {
            $node = $this->findNode($selector);
        } catch (\Exception $e) {
            return false;
        }

        return true;
    }

    /**
     * Sync, but don't write
     */
    public function save(): void
    {
        $this->syncNode();
    }

    /**
     * Get a secret value and validate it
     * 
     * @param string the name of the variable
     * @param array a list of validations to apply
     * 
     * @return mixed
     */
    public function secret(string $name, array $validations = [])
    {
        if (isset($this->environment->secretsManager)) {
            $secretsManager = SecretsManagerFactory::instantiate($this->environment->secretsManager);
            $value = $secretsManager->get($name);
        } else {
            $secretsManager = SecretsManagerFactory::instantiate();
            $value = $secretsManager->get($name);
        }

        if (isset($validations['default'])) {
            if ($value === false) {
                $value = $validations['default'];
            }
        }
        if (in_array('required', $validations)) {
            if ($value === false || $value === '') {
                throw new \Exception(sprintf('Missing required environment variable "%s".', SecretsUtil::keyToEnv($name)));
            }
        }
        if (isset($validations['type'])) {
            switch($validations['type']) {
                case 'integer':
                    if ((string) intval($value) != $value) {
                        throw new \Exception(sprintf('Environment variable "%s" is not an integer.', $name));
                    }
                    $value = intval($value);
                    break;
                case 'string':
                    if (!is_string($value)) {
                        throw new \Exception(sprintf('Environment variable "%s" is not an string.', $name));
                    }
                    break;
                case 'float':
                    if ((string) floatval($value) != $value) {
                        throw new \Exception(sprintf('Environment variable "%s" is not an float.', $name));
                    }
                    $value = floatval($value);
                    break;
                case 'boolean':
                    $value = strtolower((string) $value);
                    if ($value != 'true' && $value != 'false' && $value != '1' && $value != '0' && $value != 'on' && $value != 'off') {
                        throw new \Exception(sprintf('Environment variable "%s" is not an boolean.', $name));
                    }
                    $value = boolval($value);
                    break;
            }
        }

        return $value;
    }

    /**
     * Find a node using a jq-style selector
     * 
     * @param string selector
     */
    private function findNode(string $selector): Node
    {
        $keys = preg_match_all('/"[^"]*"|[^.]+/', $selector, $matches);
        $aPtr = &$this->yaml;
        foreach ($matches[0] as $k) {
            $k = trim($k, '"');
            if (preg_match('/\[([0-9]+)\]/', $k, $arrMatches)) {
                $k = $arrMatches[1];
            }
            if (((is_array($aPtr)) && (array_key_exists($k, $aPtr))) || (isset($aPtr[$k]))) {
                $aPtr = &$aPtr[$k];
            } else {
                throw new \Exception(sprintf('Selector %s not found in YAML.\n', $selector));
            }
        }

        return new Node($aPtr, $selector);
    }

    /**
     * Save the latest node changes to the main tree
     * 
     * @param bool is this an interim update (another add) or a final one (save)?
     * 
     * @return void
     */
    private function syncNode(bool $interimUpdate = false): void
    {
        if ($this->activeNode instanceof Node) {
            $keys = preg_match_all('/"[^"]*"|[^.]+/', $this->activeNode->getSelector(), $matches);
            $aPtr = &$this->yaml;
            foreach ($matches[0] as $k) {
                $k = trim($k, '"');
                if (preg_match('/\[([0-9]+)\]/', $k, $arrMatches)) {
                    $k = $arrMatches[1];
                }    
                if (((is_array($aPtr)) && (array_key_exists($k, $aPtr))) || (isset($aPtr[$k]))) {
                    $aPtr = &$aPtr[$k];
                } else {
                    unset($aPtr);
                }
            }
            $aPtr = $this->activeNode->get();
        }

        // Remove empty
        if (!$interimUpdate && $this->config->save->removeEmptyNodes) {
            $this->yaml = Utils::removeEmpty($this->yaml);
        }

        // Mask values
        if (!$interimUpdate && $this->config->save->maskValues) {
            $this->yaml = Utils::maskValues($this->yaml);
        }

        // Convert nodes to array
        $this->yaml = json_decode(json_encode($this->yaml), true);
    }

}
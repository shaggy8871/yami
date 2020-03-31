<?php declare(strict_types=1);

namespace Yami\Migration;

use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use Yami\Config\Bootstrap;
use Console\Args;

abstract class AbstractMigration
{

    const ACTION_MIGRATE  = 'migrate';
    const ACTION_ROLLBACK = 'rollback';

    /**
     * @var Args
     */
    protected $args;

    /**
     * @var \stdClass
     */
    protected $config;

    /**
     * @var array
     */
    protected $yaml;

    /**
     * @var Yami\Migration\Node
     */
    protected $activeNode;

    public function __construct(string $action, \stdClass $migration, Args $args)
    {
        $this->args = $args;
        $this->config = Bootstrap::getConfig();

        $loadFlags = 
            ($this->config->load->asObject ? Yaml::PARSE_OBJECT : 0) + 
            ($this->config->load->asYamlMap ? Yaml::PARSE_OBJECT_FOR_MAP : 0) + 
            Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;

        try {
            $this->yaml = Yaml::parseFile(
                $this->config->yamlFile, 
                $loadFlags
            );
            // Convert to array
            $this->yaml = json_decode(json_encode($this->yaml), true);
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Unable to parse YAML file "%s".', $this->config->yamlFile));
        }

        switch ($action) {
            case self::ACTION_MIGRATE:
                if (method_exists($this, 'up')) {
                    $this->up();
                } else {
                    throw new \Exception(sprintf('Unable to find up() method in migration %s', $migration->uniqueId));
                }
                break;
            case self::ACTION_ROLLBACK:
                if (method_exists($this, 'down')) {
                    $this->down();
                } else {
                    throw new \Exception(sprintf('Unable to find down() method in migration %s', $migration->uniqueId));
                }
                break;
        }
    }

    /**
     * Finds and returns a tree node
     * 
     * @param string $selector
     * 
     * @return Yami\Migration\Node
     */
    public function get(string $selector): Node
    {
        // Save last changes (just in case)
        if ($this->activeNode instanceof Node) {
            $this->syncNode();
        }

        $this->activeNode = $this->findNode($selector); // don't catch exception

        return $this->activeNode;
    }

    /**
     * Checks whether the specified key exists
     * 
     * @param string $selector
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
     * Write out the YAML file
     */
    public function save()
    {
        $this->syncNode();

        $saveFlags = 
            ($this->config->save->asObject ? Yaml::DUMP_OBJECT : 0) + 
            ($this->config->save->asYamlMap ? Yaml::DUMP_OBJECT_AS_MAP : 0) + 
            ($this->config->save->asMultilineLiteral ? Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK : 0) + 
            ($this->config->save->base64BinaryData ? Yaml::DUMP_BASE64_BINARY_DATA : 0) +
            ($this->config->save->nullAsTilde ? Yaml::DUMP_NULL_AS_TILDE : 0) + 
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE + 
            Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE;

        $yaml = Yaml::dump(
            $this->yaml, 
            $this->config->save->inlineFromLevel ?? 10, 
            $this->config->save->indentation ?? 2,
            $saveFlags
        );

        if (array_key_exists('verify', $this->args->getAll())) {
            echo "\n" . $yaml;
        } else {
            file_put_contents($this->config->yamlFile, $yaml);
        }
    }

    /**
     * Identical to save() but checks that the file does not 
     * exist prior to saving.
     */
    public function create()
    {

    }

    /**
     * Identical to save() but checks that the file exists
     * prior to saving.
     */
    public function update()
    {

    }

    /**
     * Find a node using a jq-style selector
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
     */
    private function syncNode(): void
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
                    throw new \Exception(sprintf('Selector %s not found in YAML.\n', $this->activeNode->getSelector()));
                }
            }
            $aPtr = $this->activeNode->getValue();
        }

        // Remove empty
        $this->yaml = $this->removeEmpty($this->yaml);
        // Convert nodes to array
        $this->yaml = json_decode(json_encode($this->yaml), true);
    }

    /**
     * Recursively remove empty nodes
     */
    private function removeEmpty(array $i): array
    {
        foreach ($i as &$value) {
            if (is_array($value)) {
                $value = $this->removeEmpty($value);
            }
        }
        return array_filter($i, function($v) {
            return !empty($v);
        });
    }

}
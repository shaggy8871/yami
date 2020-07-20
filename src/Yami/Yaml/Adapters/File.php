<?php declare(strict_types=1);

namespace Yami\Yaml\Adapters;

use Yami\Yaml\{AbstractYamlAdapter, YamlAdapterInterface};
use DateTime;
use stdClass;

class File extends AbstractYamlAdapter implements YamlAdapterInterface
{

    public function __construct(stdClass $config, stdClass $environment)
    {
        if (!isset($environment->yaml->file)) {
            throw new \Exception('Missing setting in config (yaml.file).');
        }

        parent::__construct($config, $environment);
    }

    /**
     * Load the YAML file from disk
     * 
     * @return string
     */
    public function loadYamlContent(): string
    {
        return file_get_contents($this->environment->yaml->file);
    }

    /**
     * Save the YAML file to disk
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return string|null
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string
    {
        $yamlFile = $this->environment->yaml->file;
        if ($backup) {
            $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $yamlFile);
            file_put_contents($backupFile, trim(file_get_contents($yamlFile)));
        }

        file_put_contents($yamlFile, $yaml);

        return $backup ? $backupFile : $yamlFile;
    }

}
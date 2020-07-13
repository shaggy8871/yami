<?php declare(strict_types=1);

namespace Yami\Yaml\Adapters;

use Yami\Yaml\{AbstractAdapter, AdapterInterface};
use DateTime;

class File extends AbstractAdapter implements AdapterInterface
{

    /**
     * Load the YAML file from disk
     * 
     * @return string
     */
    public function loadYamlContent(): string
    {
        return file_get_contents($this->environment->yamlFile);
    }

    /**
     * Save the YAML file to disk
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return string
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string
    {
        if ($backup) {
            $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $this->environment->yamlFile);
            file_put_contents($backupFile, trim(file_get_contents($this->environment->yamlFile)));
        }

        file_put_contents($this->environment->yamlFile, $yaml);

        return $backup ? $backupFile : $this->environment->yamlFile;
    }

}
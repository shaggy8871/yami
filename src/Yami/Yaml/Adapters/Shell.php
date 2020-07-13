<?php declare(strict_types=1);

namespace Yami\Yaml\Adapters;

use Yami\Yaml\{AbstractAdapter, AdapterInterface};

class Shell extends AbstractAdapter implements AdapterInterface
{

    /**
     * Load the YAML file from stdin
     * 
     * @return string
     */
    public function loadYamlContent(): string
    {
        return file_get_contents('php://stdin');
    }

    /**
     * Save the YAML file to stderr
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return void
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string
    {
        file_put_contents('php://stderr', $yaml);
    }

}
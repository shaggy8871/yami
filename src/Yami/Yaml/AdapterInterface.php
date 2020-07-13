<?php declare(strict_types=1);

namespace Yami\Yaml;

Interface AdapterInterface
{

    /**
     * Load the YAML file
     * 
     * @return string
     */
    public function loadYamlContent(): string;

    /**
     * Save the YAML file
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return void
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string;

}
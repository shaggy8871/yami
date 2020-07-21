<?php declare(strict_types=1);

namespace Yami\Yaml\Adapters;

use Yami\Yaml\{AbstractYamlAdapter, YamlAdapterInterface};

class Stream extends AbstractYamlAdapter implements YamlAdapterInterface
{

    /**
     * @var string
     * 
     * We save this here because you can only read it once
     */
    private $stdin;

    /**
     * Load the YAML file from stdin
     * 
     * @return string
     */
    public function loadYamlContent(): string
    {
        if ($this->stdin) {
            return $this->stdin;
        }

        $stdin = '';
        while ($line = fgets(STDIN)) {
            $stdin .= $line;
        }

        $this->stdin = trim($stdin);

        return $this->stdin;
    }

    /**
     * Save the YAML file to stderr
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return string|null
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string
    {
        fwrite(STDERR, $yaml);

        return null;
    }

}
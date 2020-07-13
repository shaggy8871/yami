<?php declare(strict_types=1);

namespace Yami\Yaml;

use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use stdClass;

abstract class AbstractAdapter
{

    /**
     * @var stdClass
     */
    protected $config;

    /**
     * @var stdClass
     */
    protected $environment;

    public function __construct(stdClass $config, stdClass $environment)
    {
        $this->config = $config;
        $this->environment = $environment;
    }

    /**
     * Load the YAML file with rules specified by the config
     * 
     * @throw Exception
     * @return array
     */
    public function load(): array
    {
        $loadFlags = 
            ($this->config->load->asObject ? Yaml::PARSE_OBJECT : 0) + 
            ($this->config->load->asYamlMap ? Yaml::PARSE_OBJECT_FOR_MAP : 0) + 
            Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;

        $yamlContent = $this->loadYamlContent();

        try {
            $yaml = Yaml::parse(
                $yamlContent, 
                $loadFlags
            );
            // Convert to array
            $yaml = json_decode(json_encode($yaml), true);
        } catch (ParseException $e) {
            throw new \Exception(sprintf('Unable to parse YAML file: %s', $e->getMessage()));
        }

        return $yaml;
    }

    /**
     * Prepares the YAML for saving
     * 
     * @param array the YAML in array format
     * 
     * @return string
     */
    public function mock(array $yaml): string
    {
        $saveFlags = 
            ($this->config->save->asObject ? Yaml::DUMP_OBJECT : 0) + 
            ($this->config->save->asYamlMap ? Yaml::DUMP_OBJECT_AS_MAP : 0) + 
            ($this->config->save->asMultilineLiteral ? Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK : 0) + 
            ($this->config->save->base64BinaryData ? Yaml::DUMP_BASE64_BINARY_DATA : 0) +
            ($this->config->save->nullAsTilde ? Yaml::DUMP_NULL_AS_TILDE : 0) + 
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE + 
            Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE;

        return Yaml::dump(
            $yaml, 
            $this->config->save->inlineFromLevel ?? 10, 
            $this->config->save->indentation ?? 2,
            $saveFlags
        );
    }

    /**
     * Save the YAML file with rules specified by the config
     * 
     * @param array the YAML in array format
     * @param bool should a backup be created?
     * 
     * @return string
     */
    public function save(array $yaml, bool $backup = false): string
    {
        $yaml = $this->mock($yaml);

        return $this->saveYamlContent($yaml, $backup);
    }

}
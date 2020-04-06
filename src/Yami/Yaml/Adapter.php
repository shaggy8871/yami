<?php declare(strict_types=1);

namespace Yami\Yaml;

use Symfony\Component\Yaml\{Yaml, Exception\ParseException};

class Adapter
{

    /**
     * Load the YAML file with rules specified by the config
     * 
     * @param stdClass the config object
     * @param stdClass the environment object
     * 
     * @throw Exception
     * @return array
     */
    public static function load(\stdClass $config, \stdClass $environment): array
    {
        $loadFlags = 
            ($config->load->asObject ? Yaml::PARSE_OBJECT : 0) + 
            ($config->load->asYamlMap ? Yaml::PARSE_OBJECT_FOR_MAP : 0) + 
            Yaml::PARSE_EXCEPTION_ON_INVALID_TYPE;

        try {
            $yaml = Yaml::parseFile(
                $environment->yamlFile, 
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
     * Save the YAML file with rules specified by the config
     * 
     * @param array the YAML in array format
     * @param stdClass the config object
     * @param stdClass the environment object
     * 
     * @return void
     */
    public static function save(array $yaml, \stdClass $config, \stdClass $environment): string
    {
        $saveFlags = 
            ($config->save->asObject ? Yaml::DUMP_OBJECT : 0) + 
            ($config->save->asYamlMap ? Yaml::DUMP_OBJECT_AS_MAP : 0) + 
            ($config->save->asMultilineLiteral ? Yaml::DUMP_MULTI_LINE_LITERAL_BLOCK : 0) + 
            ($config->save->base64BinaryData ? Yaml::DUMP_BASE64_BINARY_DATA : 0) +
            ($config->save->nullAsTilde ? Yaml::DUMP_NULL_AS_TILDE : 0) + 
            Yaml::DUMP_EMPTY_ARRAY_AS_SEQUENCE + 
            Yaml::DUMP_EXCEPTION_ON_INVALID_TYPE;

        $yaml = Yaml::dump(
            $yaml, 
            $config->save->inlineFromLevel ?? 10, 
            $config->save->indentation ?? 2,
            $saveFlags
        );

        file_put_contents($environment->yamlFile, $yaml);

        return $yaml;
    }

}
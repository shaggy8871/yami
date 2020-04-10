<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use Yami\Config\{Bootstrap, Utils};
use Yami\Yaml\Adapter;
use DateTime;

class Mask implements CommandInterface
{

    /**
     * @var Yami\Console\Decorator
     */
    protected $decorator;

    public function execute(Args $args): void
    {
        $args->setAliases([
            'c' => 'config',
            'd' => 'dry-run',
            'e' => 'env',
            'n' => 'no-ansi'
        ]);

        $this->decorator = new Decorator($args);

        $config = Bootstrap::getConfig($args);
        $environment = Bootstrap::getEnvironment($args);

        $isDryRun = array_key_exists('dry-run', $args->getAll()) || array_key_exists('d', $args->getAll());
        $yamlFile = $environment->yamlFile;

        Bootstrap::createMockYaml($args);

        $yaml = Adapter::load($config, $environment);
        $yaml = Utils::maskValues($yaml);
        $yaml = Adapter::save($yaml, $config, $environment);

        Bootstrap::deleteMockYaml($args);

        if ($isDryRun) {
            echo $yaml . "\n";
        } else {
            $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $yamlFile);
            file_put_contents($backupFile, trim(file_get_contents($yamlFile)));
            file_put_contents($yamlFile, $yaml);
            $this->decorator->write([
                [sprintf("Masked %s. The original has been backed up as %s.\n\n", $yamlFile, $backupFile), 'white']
            ]);
        }

    }

}
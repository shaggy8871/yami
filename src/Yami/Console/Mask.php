<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, StdOut};
use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use Yami\Config\{Bootstrap, Utils};
use Yami\Yaml\Adapter;
use DateTime;

class Mask implements CommandInterface
{

    public function execute(Args $args): void
    {
        $args->setAliases([
            'c' => 'config',
            'd' => 'dry-run',
            'e' => 'env',
        ]);

        $config = Bootstrap::getConfig($args);
        $environment = Bootstrap::getEnvironment($args);

        $isDryRun = isset($args->{'dry-run'});
        $yamlFile = $environment->yamlFile;

        Bootstrap::createMockYaml($args);

        $yaml = Adapter::load($config, $environment);
        $yaml = Utils::maskValues($yaml);
        $yaml = Adapter::save($yaml, $config, $environment);

        Bootstrap::deleteMockYaml($args);

        if ($isDryRun) {
            echo $yaml . "\n";
        } else {
            if ($yamlFile === 'php://stdin') {
                file_put_contents('php://stdout', $yaml);
            } else {
                $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $yamlFile);                
                file_put_contents($backupFile, trim(file_get_contents($yamlFile)));
                file_put_contents($yamlFile, $yaml);
                MessageStream::write([
                    [sprintf("Masked %s. The original has been backed up as %s.\n\n", $yamlFile, $backupFile), 'white']
                ]);    
            }
        }

    }

}
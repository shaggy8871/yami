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
            'n' => 'no-ansi'
        ]);

        if (isset($args->{'no-ansi'})) {
            StdOut::disableAnsi();
        }

        $bootstrap = Bootstrap::getInstance($args);

        $config = $bootstrap->getConfig();
        $environment = $bootstrap->getEnvironment();

        $isDryRun = isset($args->{'dry-run'});
        $yamlFile = $environment->yamlFile;

        $bootstrap->createMockYaml();

        $yaml = Adapter::load($config, $environment);
        $yaml = Utils::maskValues($yaml);
        $yaml = Adapter::save($yaml, $config, $environment);

        $bootstrap->deleteMockYaml();

        if ($isDryRun) {
            echo $yaml . "\n";
        } else {
            $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $yamlFile);
            file_put_contents($backupFile, trim(file_get_contents($yamlFile)));
            file_put_contents($yamlFile, $yaml);
            StdOut::write([
                [sprintf("Masked %s. The original has been backed up as %s.\n\n", $yamlFile, $backupFile), 'white']
            ]);
        }

    }

}
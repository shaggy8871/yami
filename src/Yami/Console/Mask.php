<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, StdOut};
use Symfony\Component\Yaml\{Yaml, Exception\ParseException};
use Yami\Config\{Bootstrap, Utils};
use Yami\Yaml\AdapterFactory;
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

        $adapter = AdapterFactory::loadFrom($config, $environment);
        $yaml = $adapter->load();
        $yaml = Utils::maskValues($yaml);

        if ($isDryRun) {
            echo $adapter->toString($yaml) . PHP_EOL;
        } else {
            $backupFile = $adapter->save($yaml, true);
            if ($backupFile) {
                StdOut::write([
                    [sprintf("Masked applied. The original has been backed up as %s.\n\n", $backupFile), 'white']
                ]);
            } else {
                StdOut::write([
                    [sprintf("Masked applied.\n\n"), 'white']
                ]);
            }
        }

    }

}
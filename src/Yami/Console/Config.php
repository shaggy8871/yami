<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, StdOut};

class Config implements CommandInterface
{

    public function execute(Args $args): void
    {
        $args->setAliases([
            'e' => 'env',
            'n' => 'no-ansi',
            'p' => 'project',
        ]);

        if (isset($args->{'no-ansi'})) {
            StdOut::disableAnsi();
        }

        $configFile = $args->project ? './' . preg_replace('/[^\w]/', '_', strtolower($args->project)) . '.php' : './config.php';

        if (file_exists($configFile)) {
            StdOut::write([
                [sprintf("Config file %s already exists.\n\n", $configFile), 'red']
            ]);
            exit(1);
        }

        file_put_contents($configFile, file_get_contents(__DIR__ . '/templates/config.template'));

        if ($args->path) {
            StdOut::write([
                [sprintf("Created config file %s at path \"%s\".\n\n", $configFile, $args->path), 'white']
            ]);
        } else {
            StdOut::write([
                [sprintf("Created config file %s.\n\n", $configFile), 'white']
            ]);
        }
    }

}
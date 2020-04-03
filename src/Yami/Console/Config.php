<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};

class Config implements CommandInterface
{

    public function execute(Args $args): void
    {
        $args->setAliases([
            'e' => 'env',
            'p' => 'project',
        ]);

        $configFile = $args->project ? './' . preg_replace('/[^\w]/', '_', strtolower($args->project)) . '.php' : './config.php';

        if (file_exists($configFile)) {
            echo Decorate::color(sprintf("Config file %s already exists.\n\n", $configFile), 'red');
            exit(1);
        }

        file_put_contents($configFile, file_get_contents(__DIR__ . '/templates/config.template'));

        if ($args->project) {
            echo Decorate::color(sprintf("Created config file %s for project \"%s\".\n\n", $configFile, $args->project), 'white');
        } else {
            echo Decorate::color(sprintf("Created config file %s.\n\n", $configFile), 'white');
        }
    }

}
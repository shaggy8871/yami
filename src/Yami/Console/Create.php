<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Config\Bootstrap;
use DateTime;

class Create implements CommandInterface
{

    public function execute(Args $args): void
    {
        $args->setAliases([
            'c' => 'config',
            'e' => 'env',
            'm' => 'migration',
        ]);

        $filename = (new DateTime())->format('Y_m_d_His') . preg_replace_callback('/[A-Z]/', function($m) {
            return '_' . strtolower($m[0]);
        }, $args->migration);

        $environment = Bootstrap::getEnvironment($args);

        file_put_contents($environment->path . '/' . $filename . '.php', str_replace('{{ClassName}}', $args->migration, file_get_contents(__DIR__ . '/templates/migration.template')));

        echo Decorate::color(sprintf("Created %s\n\n", $environment->path . '/' . $filename . '.php'), 'white');
    }

}
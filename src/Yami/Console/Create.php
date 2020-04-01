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
            'e' => 'env',
        ]);

        $filename = (new DateTime())->format('YmdHis') . preg_replace_callback('/[A-Z]/', function($m) {
            return '_' . strtolower($m[0]);
        }, $args->class);

        $environment = Bootstrap::getEnvironment($args);

        file_put_contents($environment->path . '/' . $filename . '.php', str_replace('{{ClassName}}', $args->class, file_get_contents(__DIR__ . '/templates/migration.template')));

        echo Decorate::color(sprintf("Created %s\n\n", $environment->path . '/' . $filename . '.php'), 'white');
    }

}
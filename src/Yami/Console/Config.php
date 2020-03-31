<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};

class Config implements CommandInterface
{

    public function execute(Args $args): void
    {
        file_put_contents('config.php', file_get_contents(__DIR__ . '/templates/config.template'));

        echo Decorate::color(sprintf("Created %s\n\n", 'config.php'), 'white');
    }

}
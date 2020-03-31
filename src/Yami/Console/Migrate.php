<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Migration\AbstractMigration;

class Migrate extends ConsoleAbstract
{

    const ACTION = AbstractMigration::ACTION_MIGRATE;
    const ACTION_DESCRIPTION = 'Migrating';

}
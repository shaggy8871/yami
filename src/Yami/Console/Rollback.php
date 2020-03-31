<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Migration\AbstractMigration;

class Rollback extends ConsoleAbstract
{

    const ACTION = AbstractMigration::ACTION_ROLLBACK;
    const ACTION_DESCRIPTION = 'Rolling back';

}
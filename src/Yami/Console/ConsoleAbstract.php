<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\{IteratorTrait, HistoryTrait};
use Console\{CommandInterface, Args, Decorate};
use Yami\Config\Bootstrap;

abstract class ConsoleAbstract implements CommandInterface
{

    use IteratorTrait, HistoryTrait;

    /**
     * To be defined in derived class
     */
    const ACTION = '';
    const ACTION_DESCRIPTION = '';

    /**
     * Where the history is stored
     */
    const HISTORY_FILENAME = './history.log';

    /**
     * @var Args
     */
    protected $args;

    /**
     * @var array
     */
    protected $environment;

    public function execute(Args $args): void
    {
        $args->setAliases([
            'c' => 'config',
            'd' => 'dry-run',
            'e' => 'env',
        ]);

        $startTime = microtime(true);

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);

        $this->loadHistory();

        $migrations = $this->getMigrations();
        $isDryRun = array_key_exists('dry-run', $args->getAll());

        echo Decorate::color(sprintf("Using config file %s\n", $args->config ? './' . $args->config : './config.php'), 'white');

        echo Decorate::color(sprintf("%d migrations", count($migrations)), 'blue bold') . 
             Decorate::color(sprintf(" found\n\n", count($migrations)), 'white');

        if ($isDryRun) {
            $mockYaml = Bootstrap::createMockYaml($args);
        }

        foreach($migrations as $migration) {

            include_once($migration->filePath);

            echo Decorate::color(sprintf(static::ACTION_DESCRIPTION . " %s... ", $migration->uniqueId), 'white');

            if (class_exists($migration->className)) {
                $className = $migration->className;
                try {
                    // Instantiate migration
                    new $className(static::ACTION, $migration, $args);

                    echo Decorate::color("OK!\n", 'green');

                    if ($isDryRun) {
                        echo trim(file_get_contents($mockYaml)) . "\n";
                    } else {
                        $this->addHistory($migration->className);
                    }
                } catch (\Exception $e) {
                    echo Decorate::color(sprintf("\n>> %s\n\n", $e->getMessage()), 'red');
                    echo Decorate::color(sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'grey');
                    if ($isDryRun) {
                        Bootstrap::deleteMockYaml($args);
                    }
                    exit(1);
                }
            } else {
                echo Decorate::color(sprintf("\n>> Unable to find class %s!\n\n", $migration->className), 'red');
                echo Decorate::color(sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'grey');
                if ($isDryRun) {
                    Bootstrap::deleteMockYaml($args);
                }
                exit(1);
            }

        }

        if ($isDryRun) {
            Bootstrap::deleteMockYaml($args);
        }

        echo Decorate::color(sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'grey');
    }

}
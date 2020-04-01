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
        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);

        $args->setAliases([
            'v' => 'verify',
            'e' => 'env',
        ]);

        $this->loadHistory();

        $migrations = $this->getMigrations();
        $isVerification = array_key_exists('verify', $args->getAll());

        echo Decorate::color(sprintf("%d migrations", count($migrations)), 'blue bold') . 
             Decorate::color(sprintf(" found.\n\n", count($migrations)), 'white');

        if ($isVerification) {
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

                    if ($isVerification) {
                        echo file_get_contents($mockYaml) . "\n";
                    } else {
                        $this->addHistory($migration->className);
                    }
                } catch (\Exception $e) {
                    echo Decorate::color(sprintf("\n>> %s\n\n", $e->getMessage()), 'red');
                    if ($isVerification) {
                        Bootstrap::deleteMockYaml($args);
                    }            
                    exit(1);
                }
            } else {
                echo Decorate::color(sprintf("\n>> Unable to find class %s!\n\n", $migration->className), 'red');
                if ($isVerification) {
                    Bootstrap::deleteMockYaml($args);
                }
                exit(1);
            }

        }

        if ($isVerification) {
            Bootstrap::deleteMockYaml($args);
        }

        echo "\n";
    }

}
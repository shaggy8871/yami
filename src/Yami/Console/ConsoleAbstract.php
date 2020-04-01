<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Config\Bootstrap;

abstract class ConsoleAbstract implements CommandInterface
{

    use IteratorTrait;

    /**
     * To be defined in derived class
     */
    const ACTION = '';
    const ACTION_DESCRIPTION = '';

    public function execute(Args $args): void
    {
        $args->setAliases([
            'v' => 'verify',
            'e' => 'env',
        ]);

        $migrations = $this->getMigrations($args);
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
                    new $className(static::ACTION, $migration, $args);
                    echo Decorate::color("OK!\n", 'green');
                    if ($isVerification) {
                        echo file_get_contents($mockYaml) . "\n";
                    }
                } catch (\Exception $e) {
                    echo Decorate::color(sprintf("\n>> %s\n\n", $e->getMessage()), 'red');
                    if ($isVerification) {
                        Bootstrap::deleteMockYaml($args);
                    }            
                    exit(1);
                }
            } else {
                echo Decorate::color(sprintf("\n>> Unable to find class!\n\n", $className), 'red');
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
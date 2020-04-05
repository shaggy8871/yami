<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\HistoryTrait;
use Console\{CommandInterface, Args, Decorate};
use Yami\Config\Bootstrap;
use Jfcherng\Diff\DiffHelper;

abstract class ConsoleAbstract implements CommandInterface
{

    use HistoryTrait;

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
     * @var string
     */
    protected $configId;

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
            's' => 'step',
            't' => 'target',
        ]);

        $startTime = microtime(true);

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);
        $this->configId = Bootstrap::getConfigId();

        $this->loadHistory(true);

        $lastBatchNo = $this->getLastBatchNo();
        $isDryRun = array_key_exists('dry-run', $args->getAll());
        $migrations = $this->getMigrations($lastBatchNo);

        echo Decorate::color("Using configuration: ", 'white') . Decorate::color(sprintf("%s\n", $args->config ? './' . $args->config : './config.php'), 'light_blue');
        if ($this->environment->name != $args->env) {
            echo Decorate::color(sprintf("Warning, no environment specified; defaulting to '%s'\n", $this->environment->name), 'light_red');
        } else {
            echo Decorate::color("Using environment: ", 'white') . Decorate::color(sprintf("%s\n", $this->environment->name), 'light_blue');
        }

        if (count($migrations)) {
            echo Decorate::color('Migrations file path: ', 'white') . Decorate::color(sprintf("%s\n", $this->environment->path), 'light_blue');
            echo $this->getMessages($lastBatchNo);
        }

        echo Decorate::color(sprintf("\n%d migration(s)", count($migrations)), 'green') . 
             Decorate::color(sprintf(" found\n\n", count($migrations)), 'white');

        if ($isDryRun) {
            $originalYaml = Bootstrap::createMockYaml($args);
            $diffPrev = file_get_contents($originalYaml);
        }

        $iteration = 0;
        foreach($migrations as $migration) {
            $iteration++;

            include_once($migration->filePath);

            echo Decorate::color(sprintf(static::ACTION_DESCRIPTION . " %s... ", $migration->uniqueId), 'white');

            if (class_exists($migration->className)) {
                $className = $migration->className;
                try {
                    // Instantiate migration
                    $migrationClass = new $className(static::ACTION, $migration, $args);

                    echo Decorate::color("OK!\n", 'green');

                    if ($isDryRun) {
                        $differOptions = [
                            'context' => 3,
                            'ignoreCase' => false,
                            'ignoreWhitespace' => false,
                        ];
                        $rendererOptions = [
                            'detailLevel' => 'line',
                            'language' => 'eng',
                            'resultForIdenticals' => "> no changes\n",
                        ];
                        $diffCurr = file_get_contents($this->environment->yamlFile);
                        echo DiffHelper::calculate($diffPrev, $diffCurr, 'ColourUnified', $differOptions, $rendererOptions) . "\n";
                        $diffPrev = $diffCurr;
                    } else {
                        $this->updateHistory((string) $migration->uniqueId, $lastBatchNo + 1, $iteration);
                    }
                } catch (\Exception $e) {
                    echo Decorate::color(sprintf("\n>> %s\n\n", $e->getMessage()), 'red');
                    echo Decorate::color(sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray');
                    if ($isDryRun) {
                        Bootstrap::deleteMockYaml($args);
                    }
                    exit(1);
                }
            } else {
                echo Decorate::color(sprintf("\n>> Unable to find class %s!\n\n", $migration->className), 'red');
                echo Decorate::color(sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray');
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
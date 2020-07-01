<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\HistoryTrait;
use Console\{CommandInterface, Args, StdOut};
use Yami\Config\Bootstrap;
use Jfcherng\Diff\DiffHelper;
use DateTime;

abstract class AbstractConsole implements CommandInterface
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
    protected $historyFileName;

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
            'n' => 'no-ansi',
            's' => 'step',
            't' => 'target',
        ]);

        $startTime = microtime(true);

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);
        $this->configId = Bootstrap::getConfigId();
        $this->historyFileName = Bootstrap::getConfig($args)->historyFile;

        if (isset($this->args->{'no-ansi'})) {
            StdOut::disableAnsi();
        }

        $this->loadHistory(true);

        $lastBatchNo = $this->getLastBatchNo();
        $isDryRun = isset($this->args->{'dry-run'});
        $migrations = $this->getMigrations();

        StdOut::write([
            [sprintf('Using configuration: '), 'white'],
            [sprintf("%s\n", $this->args->config ? $this->args->config : './config.php'), 'light_blue']
        ]);
        StdOut::write([
            [sprintf('Using history: '), 'white'],
            [sprintf("%s\n", $this->historyFileName), 'light_blue']
        ]);
        if ($this->environment->name != $this->args->env) {
            StdOut::write([
                [sprintf("Warning, no environment specified; defaulting to '%s'\n", $this->environment->name), 'light_red']
            ]);
        } else {
            StdOut::write([
                [sprintf('Using environment: '), 'white'],
                [sprintf("%s\n", $this->environment->name), 'light_blue']
            ]);
        }

        if (count($migrations)) {
            StdOut::write([
                [sprintf('Migrations file path: '), 'white'],
                [sprintf("%s\n", $this->environment->path), 'light_blue']
            ]);
            echo $this->getMessages($lastBatchNo);
        }

        StdOut::write([
            [sprintf("\n%d migration(s)", count($migrations)), 'green'],
            [sprintf(" found\n\n", count($migrations)), 'white']
        ]);

        if ($isDryRun) {
            $originalYaml = Bootstrap::createMockYaml($this->args);
            $diffPrev = file_get_contents($originalYaml);
        }

        $iteration = 0;
        $startTs = (new DateTime())->format('U');

        foreach($migrations as $migration) {
            $iteration++;

            include_once($migration->filePath);

            StdOut::write([
                [sprintf(static::ACTION_DESCRIPTION . " %s... ", $migration->uniqueId), 'white']
            ]);

            if (class_exists($migration->className)) {
                $className = $migration->className;
                try {
                    // Instantiate migration
                    $migrationClass = new $className(static::ACTION, $migration, $this->args);

                    StdOut::write([
                        ["OK!\n", 'green']
                    ]);

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
                        echo DiffHelper::calculate($diffPrev, $diffCurr, StdOut::isAnsiEnabled() ? 'ColourUnified' : 'Unified', $differOptions, $rendererOptions) . "\n";
                        $diffPrev = $diffCurr;
                    } else {
                        $this->updateHistory((string) $migration->uniqueId, $lastBatchNo + 1, $iteration, $startTs);
                    }
                } catch (\Exception $e) {
                    StdOut::write([
                        [sprintf("\n>> %s\n\n", $e->getMessage()), 'red'],
                        [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray']
                    ]);
                    if ($isDryRun) {
                        Bootstrap::deleteMockYaml($this->args);
                    }
                    exit(1);
                }
            } else {
                StdOut::write([
                    [sprintf("\n>> Unable to find class %s!\n\n", $migration->className), 'red'],
                    [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray']
                ]);
                if ($isDryRun) {
                    Bootstrap::deleteMockYaml($this->args);
                }
                exit(1);
            }

        }

        if ($isDryRun) {
            Bootstrap::deleteMockYaml($this->args);
        }

        StdOut::write([
            [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'grey']
        ]);
    }

}

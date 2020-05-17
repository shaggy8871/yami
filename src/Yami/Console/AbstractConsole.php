<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\HistoryTrait;
use Console\{CommandInterface, Args, StdErr, StdOut};
use Yami\Config\Bootstrap;
use Yami\Yaml\Adapter;
use Jfcherng\Diff\DiffHelper;
use DateTime;

class MessageStream extends StdErr { }

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
            'p' => 'path',
            'd' => 'dry-run',
            'e' => 'env',
            'n' => 'no-ansi',
            's' => 'step',
            't' => 'target',
            'h' => 'no-history'
        ]);

        $startTime = microtime(true);

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);
        $this->configId = Bootstrap::getConfigId();

        if (isset($this->args->{'no-ansi'})) {
            MessageStream::disableAnsi();
            StdOut::disableAnsi();
        }

        $this->loadHistory(true);

        $lastBatchNo = $this->getLastBatchNo();
        $isDryRun = isset($this->args->{'dry-run'});
        $migrations = $this->getMigrations();

        MessageStream::write([
            [sprintf('Using configuration: '), 'white'], 
            [sprintf("%s\n", $this->args->config ? $this->args->config : './config.php'), 'light_blue']
        ]);
        if ($this->environment->name != $this->args->env) {
            MessageStream::write([
                [sprintf("Warning, no environment specified; defaulting to '%s'\n", $this->environment->name), 'light_red']
            ]);
        } else {
            MessageStream::write([
                [sprintf('Using environment: '), 'white'], 
                [sprintf("%s\n", $this->environment->name), 'light_blue']
            ]);
        }

        if (count($migrations)) {
            MessageStream::write([
                [sprintf('Migrations file path: '), 'white'],
                [sprintf("%s\n", $this->environment->path), 'light_blue']
            ]);
            MessageStream::write($this->getMessages($lastBatchNo));
        }

        MessageStream::write([
            [sprintf("\n%d migration(s)", count($migrations)), 'green'], 
            [sprintf(" found\n\n", count($migrations)), 'white']
        ]);

        $config = Bootstrap::getConfig($this->args);
        $environment = Bootstrap::getEnvironment($this->args);
        $yaml = Adapter::load($config,$environment);
        $diffPrev = Adapter::stringify($yaml, $config);
        $historyRecords = [];

        $iteration = 0;
        $startTs = (new DateTime())->format('U');

        foreach($migrations as $migration) {
            $iteration++;

            include_once($migration->filePath);

            MessageStream::write([
                [sprintf(static::ACTION_DESCRIPTION . " %s... \n", $migration->uniqueId), 'white']
            ]);

            if (class_exists($migration->className)) {
                $className = $migration->className;
                try {
                    // Instantiate migration
                    $migrationClass = new $className(static::ACTION, $yaml, $migration, $this->args);
                    $yaml = $migrationClass->save();

                    MessageStream::write([
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
                        $diffCurr = Adapter::stringify($yaml, $config);
                        echo DiffHelper::calculate($diffPrev, $diffCurr, MessageStream::isAnsiEnabled() ? 'ColourUnified' : 'Unified', $differOptions, $rendererOptions) . "\n";
                        $diffPrev = $diffCurr;
                    } else {
                        $historyRecords[] = [ (string) $migration->uniqueId, $lastBatchNo + 1, $iteration, $startTs ];
                    }
                } catch (\Exception $e) {
                    MessageStream::write([
                        [sprintf("\n>> %s\n\n", $e->getMessage()), 'red'], 
                        ["Migrations aborted, changes not applied", 'red'],
                        [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray']
                    ]);
                    exit(1);
                }
            } else {
                MessageStream::write([
                    [sprintf("\n>> Unable to find class %s!\n\n", $migration->className), 'red'], 
                    [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'light_gray']
                ]);
                exit(1);
            }

        }

        if (!$isDryRun) {
            Adapter::save($yaml, $config, $environment);
            if (!isset($this->args->{'no-history'})) {
                foreach ($historyRecords as $rec)
                    call_user_func_array([$this,'updateHistory'],$rec);
            }
        }

        MessageStream::write([
            [sprintf("Completed in %d.2 seconds.\n\n", microtime(true) - $startTime), 'white']
        ]);
    }

}
<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, StdOut};
use Yami\Config\Bootstrap;
use Yami\Migration\AbstractMigration;

class Migrate extends AbstractConsole
{

    const ACTION = AbstractMigration::ACTION_MIGRATE;
    const ACTION_DESCRIPTION = 'Migrating';
    const LOAD_FULL_HISTORY = false;

    /**
     * Return a list of migrations to run
     * 
     * @return array
     */
    protected function getMigrations(): array
    {
        $path = $this->environment->path . '/';

        $migrations = [];

        foreach (glob($path . "*.php") as $migration) {
            $uniqueId = str_replace([$path, '.php'], '', $migration);

            // Extract version
            preg_match('/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}/', $uniqueId, $matches);
            if ($matches[0]) {
                $version = $matches[0];
            } else {
                throw new \Exception("Unable to parse version from migration.");
            }

            $className = preg_replace_callback('/(_[a-z_])/', function($m) {
                return strtoupper(str_replace('_', '', $m[0]));
            }, str_replace($version, '', $uniqueId));

            if ($this->migrationHasRun($uniqueId)) {
                continue;
            }

            $migrations[] = (object) [
                'filePath'  => $migration,
                'uniqueId'  => $uniqueId,
                'version'   => $version,
                'className' => $className,
            ];
        }

        return $migrations;
    }

    /**
     * Returns action specific messages for display
     * 
     * @param int the batch number if applicable
     * 
     * @return string
     */
    protected function getMessages(int $batchNo): string
    {
        $messages = '';

        if (isset($this->environment->secretsManager) && isset($this->environment->secretsManager->adapter)) {
            $messages .= StdOut::format([
                [sprintf('Using secrets manager: '), 'white'], 
                [sprintf("%s\n", $this->environment->secretsManager->adapter), 'light_blue']
            ]);
        }

        $messages .= StdOut::format([
            [sprintf('Batch id: '), 'white'], 
            [sprintf("%d\n", $batchNo + 1), 'light_blue']
        ]);

        if (isset($this->args->{'dry-run'})) {
            $messages .= StdOut::format([
                [sprintf("\nStarting dry run...\n"), 'yellow'], 
            ]);
        }

        return $messages;
    }

    /**
     * Update the history
     * 
     * @param string the migration name
     * @param int the batch number
     * @param int the batch iteration
     * @param string the starting unix timestamp
     * 
     * @return: void
     */
    protected function updateHistory(string $migration, int $batchNo, int $iteration, string $startTs): void
    {
        $this->addToHistory($migration, $batchNo, $iteration, $startTs);
    }

    /**
     * Save the history to file
     * 
     * @return void
     */
    protected function saveHistory(): void
    {
        foreach($this->unsavedChanges as $history) {
            file_put_contents($this->historyFileName, $history . "\n", FILE_APPEND);
        }

        $this->unsavedChanges = [];
    }

}
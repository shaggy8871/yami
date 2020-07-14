<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, StdOut};
use Yami\Migration\AbstractMigration;

class Rollback extends AbstractConsole
{

    const ACTION = AbstractMigration::ACTION_ROLLBACK;
    const ACTION_DESCRIPTION = 'Rolling back';
    const LOAD_FULL_HISTORY = false;

    /**
     * Return a list of migrations to run
     * 
     * @return array
     */
    protected function getMigrations(): array
    {
        if ($this->args->step && is_numeric($this->args->step)) {
            $migrations = $this->getMigrationsToStep((int) $this->args->step);
        } else
        if ($this->args->target) {
            $migrations = $this->getMigrationsToTarget($this->args->target);
        } else {
            $migrations = $this->getLastMigrationBatch();
        }

        return array_map(function(\stdClass $m) {

            // Extract version
            preg_match('/[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{6}/', $m->migration, $matches);
            if ($matches[0]) {
                $version = $matches[0];
            } else {
                throw new \Exception("Unable to parse version from migration.");
            }

            // Extract class name
            $className = preg_replace_callback('/(_[a-z])/', function($m) {
                return strtoupper(str_replace('_', '', $m[0]));
            }, str_replace($version, '', $m->migration));

            return (object) [
                'filePath'  => $this->environment->path . '/' . $m->migration . '.php',
                'uniqueId'  => $m->migration,
                'version'   => $version,
                'className' => $className,
            ];

        }, $migrations);
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

        if ($this->args->step && is_numeric($this->args->step)) {
            $messages .= StdOut::format([
                [sprintf('Rolling back steps: '), 'white'], 
                [sprintf("%d\n", (int) $this->args->step), 'light_blue']
            ]);
        } else
        if ($this->args->target) {
            $messages .= StdOut::format([
                [sprintf('Rolling back to target: '), 'white'], 
                [sprintf("%s\n", $this->args->target), 'light_blue']
            ]);
        } else {
            $messages .= StdOut::format([
                [sprintf('Rolling back to batch: '), 'white'], 
                [sprintf("%d\n", $batchNo), 'light_blue']
            ]);
        }

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
     * @param int the batch number (unused)
     * @param int the batch iteration (unused)
     * @param string the starting unix timestamp (unused)
     * 
     * @return: void
     */
    protected function updateHistory(string $migration, int $batchNo, int $iteration, string $startTs): void
    {
        $this->removeFromHistory($migration);
    }

    /**
     * Save the history to file
     * 
     * @return void
     */
    protected function saveHistory(): void
    {
        file_put_contents($this->historyFileName, implode("\n", array_map(function($j) {
            return json_encode($j);
        }, $this->history)) . "\n");
    }

}
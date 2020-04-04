<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Config\Bootstrap;
use Yami\Migration\AbstractMigration;

class Migrate extends ConsoleAbstract
{

    const ACTION = AbstractMigration::ACTION_MIGRATE;
    const ACTION_DESCRIPTION = 'Migrating';

    /**
     * Return a list of migrations to run
     * 
     * @param int the last batch number (only used for rollback)
     * 
     * @return array
     */
    protected function getMigrations(int $lastBatchNo): array
    {
        $environment = Bootstrap::getEnvironment($this->args);
        $path = $environment->path . '/';

        $migrations = [];

        foreach (glob($path . "*.php") as $migration) {
            $uniqueId = str_replace([$path, '.php'], '', $migration);

            // Extract version
            preg_match('/[0-9]+/', $uniqueId, $matches);
            if ($matches[0]) {
                $version = $matches[0];
            } else {
                throw new \Exception("Unable to parse version from migration.");
            }

            $className = preg_replace_callback('/(_[a-z])/', function($m) {
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
        return Decorate::color('Batch id: ', 'white') . Decorate::color(sprintf("%d\n", $batchNo + 1), 'light_blue');
    }

    /**
     * Update the history
     * 
     * @param string the migration name
     * @param int the batch number
     * @param int the batch iteration
     * 
     * @return: void
     */
    protected function updateHistory(string $migration, int $batchNo, int $iteration): void
    {
        $this->addToHistory($migration, $batchNo, $iteration);
    }

}
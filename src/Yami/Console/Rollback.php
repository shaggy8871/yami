<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{CommandInterface, Args, Decorate};
use Yami\Migration\AbstractMigration;

class Rollback extends AbstractConsole
{

    const ACTION = AbstractMigration::ACTION_ROLLBACK;
    const ACTION_DESCRIPTION = 'Rolling back';

    /**
     * Return a list of migrations to run
     * 
     * @param int the last batch number (only used for rollback)
     * 
     * @return array
     */
    protected function getMigrations(int $lastBatchNo): array
    {
        if ($this->args->step && is_numeric($this->args->step)) {
            $migrations = $this->getMigrationsToStep((int) $this->args->step);
        } else
        if ($this->args->target) {
            $migrations = $this->getMigrationsToTarget($this->args->target);
        } else {
            $migrations = $this->getMigrationsFromBatch($lastBatchNo);
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
        if ($this->args->step && is_numeric($this->args->step)) {
            return Decorate::color('Rolling back steps: ', 'white') . Decorate::color(sprintf("%d\n", (int) $this->args->step), 'light_blue');
        } else
        if ($this->args->target) {
            return Decorate::color('Rolling back to target: ', 'white') . Decorate::color(sprintf("%s\n", $this->args->target), 'light_blue');
        } else {
            return Decorate::color('Rolling back to batch: ', 'white') . Decorate::color(sprintf("%d\n", $batchNo), 'light_blue');
        }
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
        $this->removeFromHistory($migration, $batchNo . '.' . $iteration);
    }

    /**
     * Return all history events that match a particular batch number
     * 
     * @param string the batch number
     * 
     * @return array
     */
    protected function getMigrationsFromBatch(int $batchNo): array
    {
        return array_filter(array_reverse($this->history), function(\stdClass $h) use ($batchNo) {
            list($b, $iteration) = explode('.', $h->batchId);
            return $b == $batchNo;
        });
    }

    /**
     * Return all history events until a specific step is reached
     * 
     * @param string the number of steps
     * 
     * @return array
     */
    protected function getMigrationsToStep(int $steps): array
    {
        $i = 0;
        return array_filter(array_reverse($this->history), function(\stdClass $h) use ($steps, &$i) {
            $i++;
            return $i <= $steps;
        });
    }

    /**
     * Return all history events until a specific migration is reached
     * 
     * @param string the target migration
     * 
     * @return array
     */
    protected function getMigrationsToTarget(string $migration): array
    {
        $found = false;
        return array_filter(array_reverse($this->history), function(\stdClass $h) use ($migration, &$found) {
            if ($h->migration == $migration) {
                $found = true; return true;
            }
            return !$found;
        });
    }

}
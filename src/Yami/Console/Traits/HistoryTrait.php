<?php declare(strict_types=1);

namespace Yami\Console\Traits;

use Yami\Config\Bootstrap;

trait HistoryTrait
{

    /**
     * @var array
     */
    protected $history = [];

    /**
     * @var int
     */
    protected $lastBatchNo = 0;

    /**
     * Add a migration to the history
     * 
     * @param string the migration name
     * @param int the batch id
     * @param int the batch iteration
     * @param string the starting unix timestamp
     * 
     * @return void
     */
    protected function addToHistory(string $migration, int $batchNo, int $iteration, string $startTs): void
    {
        $configId = $this->configId;
        $environmentName = $this->environment->name;

        $history = [
            'configId' => $configId,
            'environmentName' => $environmentName,
            'migration' => $migration,
            'ts' => $startTs,
            'batchId' => $batchNo . '.' . $iteration,
        ];

        $this->history[$configId . '_' . $environmentName . '_' . $migration] = $history;
        $this->lastBatchNo = $batchNo;

        file_put_contents(self::HISTORY_FILENAME, json_encode($history) . "\n", FILE_APPEND);
    }

    /**
     * Removes a migration from memory and replaces the history.log file altogether
     * 
     * @param string the migration name
     * 
     * @return array
     */
    protected function removeFromHistory(string $migration): void
    {
        unset($this->history[$this->configId . '_' . $this->environment->name . '_' . $migration]);

        $history = array_map(function($j) {
            // Encode rows
            return json_encode($j);
        }, $this->history);

        // Save to file
        file_put_contents(self::HISTORY_FILENAME, implode("\n", $history) . "\n");
    }

    /**
     * Returns if the migration has run
     * 
     * @param string the migration name
     * 
     * @return bool
     */
    protected function migrationHasRun(string $migration): bool
    {
        return (isset($this->history[$this->configId . '_' . $this->environment->name . '_' . $migration]));
    }

    /**
     * Returns the last batch id
     * 
     * @returns int
     */
    protected function getLastBatchNo(): int
    {
        return $this->lastBatchNo;
    }

    /**
     * Loads the full or filtered history into memory
     * 
     * @param bool whether to filter the history to the current config and environment
     * @param array load from array, not file
     * 
     * @return void
     */
    protected function loadHistory(bool $filtered = false, ?array $history = null): void
    {
        if (!$history) {
            if (file_exists(self::HISTORY_FILENAME)) {
                $history = explode("\n", trim(file_get_contents(self::HISTORY_FILENAME)));
            } else {
                $history = [];
            }
        }

        // Decode rows
        $configId = $this->configId;
        $environmentName = $this->environment->name;

        $this->history = [];

        foreach($history as $h) {
            $json = json_decode($h);
            $compositeKey = $configId . '_' . $environmentName . '_' . $json->migration;
            if ($filtered) {
                if ($json->configId == $configId && $json->environmentName == $environmentName) {
                    $this->history[$compositeKey] = $json;
                }
            } else {
                $this->history[$compositeKey] = $json;
            }
            // Always filter batch number
            if ($json->configId == $configId && $json->environmentName == $environmentName) {
                list($batchNo, $iteration) = explode('.', $json->batchId);
                if ((int) $batchNo > $this->lastBatchNo) {
                    $this->lastBatchNo = (int) $batchNo;
                }
            }
        }
    }

    /**
     * Return the last batch of events run
     * 
     * @return array
     */
    protected function getLastMigrationBatch(): array
    {
        $historyReversed = array_reverse($this->history);
        $lastTimestamp = current($historyReversed)->ts ?? 0;
        return array_filter($historyReversed, function(\stdClass $h) use ($lastTimestamp) {
            return $h->ts == $lastTimestamp;
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
        if ($steps > count($this->history)) {
            throw new \Exception(sprintf('Unable to roll back %d step(s).', $steps));
        }

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
        if (!isset($this->history[$this->configId . '_' . $this->environment->name . '_' . $migration])) {
            throw new \Exception(sprintf('Unable to find target "%s".', $migration));
        }

        $found = false;
        return array_filter(array_reverse($this->history), function(\stdClass $h) use ($migration, &$found) {
            if ($h->migration == $migration) {
                $found = true; return true;
            }
            return !$found;
        });
    }

}
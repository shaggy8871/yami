<?php declare(strict_types=1);

namespace Yami\Console\Traits;

use Yami\Config\Bootstrap;
use DateTime;

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
     * 
     * @return void
     */
    protected function addToHistory(string $migration, int $batchNo, int $iteration): void
    {
        $configId = $this->configId;
        $environmentName = $this->environment->name;

        $history = [
            'configId' => $configId,
            'environmentName' => $environmentName,
            'migration' => $migration,
            'ts' => (new DateTime())->format('U'),
            'batchId' => $batchNo . '.' . $iteration,
        ];

        $this->history[$configId . '_' . $environmentName . '_' . $migration] = $history;
        $this->lastBatchNo = $batchNo;

        file_put_contents(self::HISTORY_FILENAME, json_encode($history) . "\n", FILE_APPEND);
    }

    /**
     * Removes a batch or migration and replaces the history.log file altogether
     * 
     * @param string the migration id
     * @param int the batch id
     * 
     * @return array
     */
    protected function removeFromHistory(string $migration = '', string $batchId = ''): void
    {
        $this->loadHistory();

        $history = array_map(function($j) {
            // Encode rows
            return json_encode($j);
        }, array_filter($this->history, function(\stdClass $j) use ($batchId, $migration) {
            // Filter rows
            return ($migration != '' && $migration == $j->migration) || ($batchId != '' && $batchId == $j->batchId) ? false : true;
        }));

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
     * 
     * @return void
     */
    protected function loadHistory(bool $filtered = false): void
    {
        if (file_exists(self::HISTORY_FILENAME)) {
            $history = explode("\n", trim(file_get_contents(self::HISTORY_FILENAME)));
        } else {
            $history = [];
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
                    list($batchNo, $iteration) = explode('.', $json->batchId);
                    if ((int) $batchNo > $this->lastBatchNo) {
                        $this->lastBatchNo = (int) $batchNo;
                    }
                }
            } else {
                $this->history[$compositeKey] = $json;
                list($batchNo, $iteration) = explode('.', $json->batchId);
                if ((int) $batchNo > $this->lastBatchNo) {
                    $this->lastBatchNo = (int) $batchNo;
                }
            }
        }
    }

}
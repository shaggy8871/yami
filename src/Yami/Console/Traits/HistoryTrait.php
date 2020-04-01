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
     * Add a migration to the history
     * 
     * @param string the migration name
     * 
     * @return void
     */
    protected function addHistory(string $migration): void
    {
        $this->history[] = $this->environment->name . '_' . md5($migration);

        $this->saveHistory();
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
        return (in_array($this->environment->name . '_' . md5($migration), $this->history));
    }

    /**
     * Loads the history for the environment
     * 
     * @return void
     */
    protected function loadHistory(): void
    {
        if (file_exists(self::HISTORY_FILENAME)) {
            $this->history = explode("\n", trim(file_get_contents(self::HISTORY_FILENAME)));
        } else {
            $this->history = [];
        }
    }

    /**
     * Saves the history for an environment
     * 
     * @return array
     */
    protected function saveHistory(): void
    {
        sort($this->history);

        file_put_contents(self::HISTORY_FILENAME, implode("\n", $this->history) . "\n");
    }

}
<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\HistoryTrait;
use Console\{CommandInterface, Args, StdOut};
use Yami\Config\Bootstrap;
use DateTime;

class History implements CommandInterface
{

    use HistoryTrait;

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
            'e' => 'env',
            'n' => 'no-ansi'
        ]);

        if (isset($args->{'no-ansi'})) {
            StdOut::disableAnsi();
        }

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);
        $this->configId = Bootstrap::getConfigId();

        $this->loadHistory(true);

        StdOut::write([
            [sprintf('Using configuration: '), 'white'], 
            [sprintf("%s\n", $this->args->config ? $this->args->config : './config.php'), 'light_blue']
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

        StdOut::write([
            [sprintf("\nDate                | Batch ID | Migration \n"), 'white'], 
            [sprintf("-----------------------------------------------------------------------------------\n"), 'white'], 
        ]);

        foreach(array_reverse($this->history) as $history) {
            StdOut::write([
                [sprintf("%s | %s | %s \n", DateTime::createFromFormat('U', $history->ts)->format('Y-m-d H:i:s'), str_pad($history->batchId, 8), substr($history->migration, 0, 50)), 'white'], 
            ]);
        }

        if (!count($this->history)) {
            StdOut::write([
                [sprintf("No migrations found.\n"), 'white']
            ]);    
        }

        StdOut::write([
            [sprintf("\n"), 'grey']
        ]);

    }

}
<?php declare(strict_types=1);

namespace Yami\Console;

use Yami\Console\Traits\HistoryTrait;
use Console\{CommandInterface, Args };
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
            MessageStream::disableAnsi();
        }

        $this->args = $args;
        $this->environment = Bootstrap::getEnvironment($this->args);
        $this->configId = Bootstrap::getConfigId();

        $this->loadHistory(true);

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

        MessageStream::write([
            [sprintf("\nDate                | Batch ID | Migration \n"), 'white'], 
            [sprintf("-----------------------------------------------------------------------------------\n"), 'white'], 
        ]);

        foreach(array_reverse($this->history) as $history) {
            MessageStream::write([
                [sprintf("%s | %s | %s \n", DateTime::createFromFormat('U', $history->ts)->format('Y-m-d H:i:s'), str_pad($history->batchId, 8), substr($history->migration, 0, 50)), 'white'], 
            ]);
        }

        if (!count($this->history)) {
            MessageStream::write([
                [sprintf("No migrations found.\n"), 'white']
            ]);    
        }

        MessageStream::write([
            [sprintf("\n"), 'grey']
        ]);

    }

}
<?php declare(strict_types=1);

namespace Yami\Console;

use Console\{Args, Decorate};

class Decorator
{

    /**
     * @var bool
     */
    protected $colourEnabled = true;

    public function __construct(Args $args)
    {
        $this->colourEnabled = !(array_key_exists('no-ansi', $args->getAll()) || array_key_exists('n', $args->getAll()));
    }

    /**
     * Format content
     * 
     * @param array the messages to format
     * 
     * @return string
     */
    public function format(array $messages = []): string
    {
        $output = '';

        foreach($messages as $message) {
            list($text, $colors) = $message;
            if ($this->colourEnabled) {
                $output .= Decorate::color($text, $colors);
            } else {
                $output .= $text;
            }
        }

        return $output;
    }

    /**
     * Write content to stdout
     * 
     * @param array the messages to write
     * 
     * @return void
     */
    public function write(array $messages = []): void
    {
        echo $this->format($messages);
    }

    /**
     * Returns true if colour coding is enabled
     * 
     * @return bool
     */
    public function isColourEnabled(): bool
    {
        return $this->colourEnabled;
    }

}
<?php declare(strict_types=1);

namespace Yami\Secrets;

interface SecretsManagerInterface
{

    /**
     * Must get and return a string value based on a key lookup
     * 
     * @param string the key to look up
     * 
     * @return string
     */
    public function get(string $key): string;

}
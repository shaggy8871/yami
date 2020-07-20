<?php declare(strict_types=1);

namespace Yami\Secrets\Adapters;

use Yami\Secrets\{SecretsManagerInterface, Utils};

class Local implements SecretsManagerInterface
{

    /**
     * Must get and return a string value based on a key lookup
     * 
     * @param string the key to look up
     * 
     * @return string
     */
    public function get(string $key): string
    {
        if (getenv(Utils::keyToEnv($key))) {
            return getenv(Utils::keyToEnv($key));
        }

        throw new \Exception(sprintf("Secret \"%s\" not found in environment variable \"%s\".", $key, Utils::keyToEnv($key)));
    }

}
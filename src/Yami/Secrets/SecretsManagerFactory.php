<?php declare(strict_types=1);

namespace Yami\Secrets;

use Yami\Secrets\Adapters\{Local, SSM};

class SecretsManagerFactory
{

    public static $knownAdapters = [
        'local' => Local::class,
        'ssm'   => SSM::class,
    ];

    public static function instantiate(?\stdClass $secretsManager = null): SecretsManagerInterface
    {
        if ($secretsManager && isset($secretsManager->adapter)) {
            if (isset(static::$knownAdapters[$secretsManager->adapter])) {
                $class = static::$knownAdapters[$secretsManager->adapter];
            } else
            if (class_exists($secretsManager->adapter)) {
                $class = $secretsManager->adapter;
            }

            if (isset($class)) {
                $instance = new $class($secretsManager);
                if ($instance instanceof SecretsManagerInterface) {
                    return $instance;
                }
            }

            throw new \Exception(sprintf('Unable to find or instantiate secrets manager class %s.', $secretsManager->adapter));
        }

        return new Local($secretsManager);
    }

}
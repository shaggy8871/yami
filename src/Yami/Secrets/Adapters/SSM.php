<?php declare(strict_types=1);

namespace Yami\Secrets\Adapters;

use Yami\Secrets\{SecretsManagerInterface, Utils};
use Aws\Ssm\{SsmClient, Exception\SsmException};

class SSM implements SecretsManagerInterface
{

    /**
     * @var stdClass
     */
    protected $config;

    /**
     * @var Aws\Ssm\SsmClient
     */
    protected $client;

    public function __construct(\stdClass $config)
    {
        $this->config = $config;

        if (!class_exists("Aws\Ssm\SsmClient")) {
            throw new \Exception('AWS SSM client is not installed.');
        }

        $awsConfig = [
            'region'  => $this->config->credentials->region,
            'version' => $this->config->credentials->version ?? 'latest',
        ];
        if (isset($this->config->credentials->profile)) {
            $awsConfig['profile'] = $this->config->credentials->profile;
        }
        $this->client = new SsmClient($awsConfig);
    }

    /**
     * Must get and return a string value based on a key lookup
     * 
     * @param string the key to look up
     * 
     * @return string
     */
    public function get(string $key): string
    {
        // If set in an environment variable, use the environment value first
        if (getenv(Utils::keyToEnv($key))) {
            return getenv(Utils::keyToEnv($key));
        }

        try {
            $result = $this->client->getParameter([
                'Name' => $key,
                'WithDecryption' => true
            ]);
        } catch (SsmException $e) {
            $response = json_decode((string) $e->getResponse()->getBody());
            if ($response->__type == 'ParameterNotFound') {
                throw new \Exception(sprintf("Parameter \"%s\" not found in SSM. Also tried looking for environment variable \"%s\".", $key, Utils::keyToEnv($key)));
            } else {
                throw new \Exception(sprintf("Error accessing parameter \"%s\" in SSM: %s. Also tried looking for environment variable \"%s\".", $key, $response->__type, Utils::keyToEnv($key)));
            }
        }

        return $result->get('Parameter')['Value'] ?? '';
    }

}
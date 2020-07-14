<?php declare(strict_types=1);

namespace Yami\Yaml\Adapters;

use Yami\Yaml\{AbstractYamlAdapter, YamlAdapterInterface};
use Aws\S3\{S3Client, Exception\S3Exception};
use DateTime;
use stdClass;

class S3 extends AbstractYamlAdapter implements YamlAdapterInterface
{

    /**
     * @var string
     * 
     * We save this here to avoid multiple reads
     */
    private $body;

    /**
     * @var Aws\S3\S3Client
     */
    protected $client;

    public function __construct(stdClass $config, stdClass $environment)
    {
        if (!class_exists("Aws\S3\S3Client")) {
            throw new \Exception('AWS S3 client is not installed.');
        }

        if (!isset($environment->yaml->credentials->region)) {
            throw new \Exception('Missing S3 credentials in config (region).');
        }
        if (!isset($environment->yaml->s3->bucket)) {
            throw new \Exception('Missing S3 bucket in config.');
        }
        if (!isset($environment->yaml->s3->key)) {
            throw new \Exception('Missing S3 key in config.');
        }

        parent::__construct($config, $environment);

        $awsConfig = [
            'region'  => $environment->yaml->credentials->region,
            'version' => $environment->yaml->credentials->version ?? 'latest',
        ];
        if (isset($environment->yaml->credentials->profile)) {
            $awsConfig['profile'] = $environment->yaml->credentials->profile;
        }
        $this->client = new S3Client($awsConfig);
    }

    /**
     * Load the YAML file from disk
     * 
     * @return string
     */
    public function loadYamlContent(): string
    {
        if ($this->body) {
            return $this->body;
        }

        $result = $this->client->getObject([
            'Bucket' => $this->environment->yaml->s3->bucket,
            'Key'    => $this->environment->yaml->s3->key,
        ]);

        $this->body = (string) $result['Body'];

        return $this->body;
    }

    /**
     * Save the YAML file to disk
     * 
     * @param string the YAML string to save
     * @param bool should a backup be created?
     * 
     * @return string|null
     */
    public function saveYamlContent(string $yaml, bool $backup = false): ?string
    {
        if ($backup) {
            $backupFile = preg_replace('/.(yml|yaml)/', '_' . (new DateTime())->format('YmdHis') . '.$1', $this->environment->yaml->s3->key);
            $this->client->putObject([
                'Bucket' => $this->environment->yaml->s3->bucket,
                'Key'    => $backupFile,
                'Body'   => $yaml,
                'ACL'    => $this->environment->yaml->s3->saveACL ?? 'private',
            ]);
        }

        $this->client->putObject([
            'Bucket' => $this->environment->yaml->s3->bucket,
            'Key'    => $this->environment->yaml->s3->key,
            'Body'   => $yaml,
            'ACL'    => $this->environment->yaml->s3->saveACL ?? 'private',
        ]);

        return sprintf('https://%s.s3.amazonaws.com/%s', $this->environment->yaml->s3->bucket, ($backup ? $backupFile : $this->environment->yaml->s3->key));
    }

}
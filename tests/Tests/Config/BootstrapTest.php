<?php declare(strict_types=1);

namespace Tests\Config;

use PHPUnit\Framework\TestCase;
use Yami\Config\Bootstrap;
use Console\Args;

class BootstrapTest extends TestCase
{

    protected $tempFile;

    public function setUp(): void
    {
        $this->tempFile = tempnam('./', 'config-test-');

        file_put_contents($this->tempFile, '<?php
return [
    \'environments\' => [
        \'default\' => [
            \'yamlFile\' => \'default.yml\',
            \'path\' => \'./migrations/default\',
        ],
        \'production\' => [
            \'yamlFile\' => \'production.yml\',
            \'path\' => \'./migrations/production\',
            \'historyFileName\' => \'./production.log\',
        ],
    ]
];');
    }

    public function tearDown(): void
    {
        unlink($this->tempFile);
    }

    public function testGetConfigMissingFile(): void
    {
        $this->expectException(\Exception::class);

        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);
        $config = $bootstrap->getConfig();

        $bootstrap->clearConfig();
    }

    public function testGetConfigFromFile(): void
    {
        $args = new Args([
            '',
            '--config=' . $this->tempFile,
        ]);

        $bootstrap = Bootstrap::getInstance($args);
        $config = $bootstrap->getConfig();

        $this->assertEquals($config->load->asObject, false);
        $this->assertEquals($config->load->asYamlMap, false);
        $this->assertEquals($config->save->indentation, 2);
        $this->assertEquals($config->save->maskValues, false);
        $this->assertEquals($config->save->removeEmptyNodes, true);
        $this->assertEquals($config->save->inlineFromLevel, 10);
        $this->assertEquals($config->save->asObject, false);
        $this->assertEquals($config->save->asYamlMap, false);
        $this->assertEquals($config->save->asMultilineLiteral, false);
        $this->assertEquals($config->save->base64BinaryData, false);
        $this->assertEquals($config->save->nullAsTilde, false);
        $this->assertEquals($config->environments->default->yamlFile, 'default.yml');
        $this->assertEquals($config->environments->default->path, './migrations/default');
        $this->assertEquals($config->environments->production->yamlFile, 'production.yml');
        $this->assertEquals($config->environments->production->path, './migrations/production');
        $this->assertEquals($config->environments->production->historyFileName, './production.log');
        $this->assertEquals($config->historyFileName, './history.log');

        $args = new Args([
            '',
            '--config=' . $this->tempFile,
            '--env=production',
        ]);

        $bootstrap->clearConfig();

        $bootstrap->setArgs($args);
        $config = $bootstrap->getConfig();

        $this->assertEquals($config->historyFileName, './production.log');

        $bootstrap->clearConfig();
    }

    public function testGetEnvironment(): void
    {
        $args = new Args([
            '',
            '--config=' . $this->tempFile,
        ]);

        $bootstrap = Bootstrap::getInstance($args);
        $environment = $bootstrap->getEnvironment();

        $this->assertEquals($environment->yamlFile, 'default.yml');
        $this->assertEquals($environment->path, './migrations/default');
        $this->assertEquals($environment->name, 'default');

        $bootstrap->clearConfig();

        $args = new Args([
            '',
            '--config=' . $this->tempFile,
            '--env=production',
        ]);

        $bootstrap = Bootstrap::getInstance($args);
        $environment = $bootstrap->getEnvironment();

        $this->assertEquals($environment->yamlFile, 'production.yml');
        $this->assertEquals($environment->path, './migrations/production');
        $this->assertEquals($environment->historyFileName, './production.log');
        $this->assertEquals($environment->name, 'production');

        $bootstrap->clearConfig();
    }

    public function testGetConfigId(): void
    {
        $args = new Args([
            '',
            '--config=' . $this->tempFile,
        ]);

        $bootstrap = Bootstrap::getInstance($args);
 
        $config = $bootstrap->getConfig();
        $configId = $bootstrap->getConfigId();

        $this->assertEquals($configId, str_replace('-', '_', basename($this->tempFile)));

        $bootstrap->clearConfig();
    }

}
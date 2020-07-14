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
            \'yaml\' => [\'file\' => \'default.yml\'],
            \'migrations\' => [\'path\' => \'./migrations/default\'],
        ],
        \'production\' => [
            \'yaml\' => [\'file\' => \'production.yml\'],
            \'migrations\' => [\'path\' => \'./migrations/production\'],
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
        $this->assertEquals($config->environments->default->yaml->file, 'default.yml');
        $this->assertEquals($config->environments->default->migrations->path, './migrations/default');
        $this->assertEquals($config->environments->production->yaml->file, 'production.yml');
        $this->assertEquals($config->environments->production->migrations->path, './migrations/production');
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

        $this->assertEquals($environment->yaml->file, 'default.yml');
        $this->assertEquals($environment->migrations->path, './migrations/default');
        $this->assertEquals($environment->name, 'default');

        $bootstrap->clearConfig();

        $args = new Args([
            '',
            '--config=' . $this->tempFile,
            '--env=production',
        ]);

        $bootstrap = Bootstrap::getInstance($args);
        $environment = $bootstrap->getEnvironment();

        $this->assertEquals($environment->yaml->file, 'production.yml');
        $this->assertEquals($environment->migrations->path, './migrations/production');
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
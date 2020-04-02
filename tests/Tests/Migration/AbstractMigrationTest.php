<?php declare(strict_types=1);

namespace Tests\Migration;

use PHPUnit\Framework\TestCase;
use Yami\Migration\{AbstractMigration, Node};
use Yami\Console\Migrate;
use Yami\Config\Bootstrap;
use Console\Args;

class AbstractMigrationTest extends TestCase
{

    public function testRootNodeMigration(): void
    {
        $args = new Args([]);

        // Seed the config
        Bootstrap::seedConfig([
            'environments' => [
                'default' => [
                    'yamlFile' => './tests/default.yaml',
                    'path' => './tests/migrations',
                ],
            ]
        ]);
        Bootstrap::createMockYaml($args);

        $migration = (object) [
            'filePath' => './tests/migrations/0000000000_test_class.php',
            'uniqueId' => '0000000000_test_class'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \TestClass(Migrate::ACTION, $migration, $args);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node(['foo' => 'baz'], '.'));

        Bootstrap::deleteMockYaml($args);
    }

}
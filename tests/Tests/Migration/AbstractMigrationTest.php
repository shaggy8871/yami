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
            'filePath' => './tests/migrations/0000000000_root_node_migration.php',
            'uniqueId' => '0000000000_root_node_migration'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \RootNodeMigration(Migrate::ACTION, $migration, $args);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node([
            'foo' => 'baz', 
            'bar' => [
                'baz' => 'boo'
                ]
            ], '.'));

        Bootstrap::deleteMockYaml($args);
    }

    public function testRemoveEmpty(): void
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
            'filePath' => './tests/migrations/0000000001_remove_empty.php',
            'uniqueId' => '0000000001_remove_empty'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \RemoveEmpty(Migrate::ACTION, $migration, $args);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node(['foo' => 'bar'], '.'));

        Bootstrap::deleteMockYaml($args);
    }

}
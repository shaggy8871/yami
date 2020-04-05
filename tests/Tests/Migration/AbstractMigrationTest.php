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
                    'path' => './tests/migrations',
                    'yamlFile' => 'default.yaml',
                ],
            ]
        ]);
        Bootstrap::createMockYaml($args, "foo: bar");

        $migration = (object) [
            'filePath' => './tests/migrations/0000000000_root_node_migration.php',
            'uniqueId' => '0000000000_root_node_migration'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \RootNodeMigration(Migrate::ACTION, $migration, $args);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node([
            'foo' => 'baz'
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
                    'path' => './tests/migrations',
                    'yamlFile' => 'default.yaml',
                ],
            ]
        ]);
        Bootstrap::createMockYaml($args, "foo: bar\nbar:\n  baz: boo");

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

    public function testFindNodeElement(): void
    {
        $args = new Args([]);

        // Seed the config
        Bootstrap::seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yamlFile' => 'default.yaml',
                ],
            ]
        ]);
        Bootstrap::createMockYaml($args, "foo: \n  bar:\n    - element1");

        $migration = (object) [
            'filePath' => './tests/migrations/0000000002_find_node_element.php',
            'uniqueId' => '0000000002_find_node_element'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \FindNodeElement(Migrate::ACTION, $migration, $args);

        $this->assertEquals($migrationInstance->get('.foo.bar.[0]'), new Node('element1', '.foo.bar.[0]'));
    }

    public function testAddElementToMap(): void
    {
        $args = new Args([]);

        // Seed the config
        Bootstrap::seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yamlFile' => 'default.yaml',
                ],
            ]
        ]);
        $mockYaml = Bootstrap::createMockYaml($args, "foo: \n  bar: baz");

        $environment = Bootstrap::getEnvironment($args);

        $migration = (object) [
            'filePath' => './tests/migrations/0000000003_add_element_to_map.php',
            'uniqueId' => '0000000003_add_element_to_map'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \AddElementToMap(Migrate::ACTION, $migration, $args);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals(file_get_contents($environment->yamlFile), "foo:\n  bar: baz\n  0: element1\n");

        Bootstrap::deleteMockYaml($args);
    }

}
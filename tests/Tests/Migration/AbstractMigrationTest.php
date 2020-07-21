<?php declare(strict_types=1);

namespace Tests\Migration;

use PHPUnit\Framework\TestCase;
use Yami\Migration\{AbstractMigration, Node};
use Yami\Console\Migrate;
use Yami\Config\Bootstrap;
use Yami\Yaml\YamlAdapterFactory;
use Console\Args;

class AbstractMigrationTest extends TestCase
{

    public function testRootNodeMigration(): void
    {
        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);

        // Seed the config
        $bootstrap->seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yaml' => [
                        'file' => 'default.yaml',
                    ],
                ],
            ]
        ]);
        file_put_contents('./default.yaml', "foo: bar");

        $migration = (object) [
            'filePath' => './tests/migrations/0000000000_root_node_migration.php',
            'uniqueId' => '0000000000_root_node_migration'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \RootNodeMigration($migration, $args, $bootstrap, YamlAdapterFactory::loadFrom($bootstrap->getConfig(), $bootstrap->getEnvironment()));
        $migrationInstance->setState();
        $migrationInstance->run(Migrate::ACTION);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node([
            'foo' => 'baz'
        ], '.'));

        unlink('./default.yaml');
    }

    public function testRemoveEmpty(): void
    {
        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);

        // Seed the config
        $bootstrap->seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yaml' => [
                        'file' => 'default.yaml',
                    ],
                ],
            ]
        ]);
        file_put_contents('./default.yaml', "foo: bar\nbar:\n  baz: boo");

        $migration = (object) [
            'filePath' => './tests/migrations/0000000001_remove_empty.php',
            'uniqueId' => '0000000001_remove_empty'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \RemoveEmpty($migration, $args, $bootstrap, YamlAdapterFactory::loadFrom($bootstrap->getConfig(), $bootstrap->getEnvironment()));
        $migrationInstance->setState();
        $migrationInstance->run(Migrate::ACTION);

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($rootNode, new Node(['foo' => 'bar'], '.'));

        unlink('./default.yaml');
    }

    public function testFindNodeElement(): void
    {
        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);

        // Seed the config
        $bootstrap->seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yaml' => [
                        'file' => 'default.yaml',
                    ],
                ],
            ]
        ]);
        file_put_contents('./default.yaml', "foo: \n  bar:\n    - element1");

        $migration = (object) [
            'filePath' => './tests/migrations/0000000002_find_node_element.php',
            'uniqueId' => '0000000002_find_node_element'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \FindNodeElement($migration, $args, $bootstrap, YamlAdapterFactory::loadFrom($bootstrap->getConfig(), $bootstrap->getEnvironment()));
        $migrationInstance->setState();
        $migrationInstance->run(Migrate::ACTION);

        $this->assertEquals($migrationInstance->get('.foo.bar.[0]'), new Node('element1', '.foo.bar.[0]'));

        unlink('./default.yaml');
    }

    public function testAddElementToMap(): void
    {
        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);

        // Seed the config
        $bootstrap->seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yaml' => [
                        'file' => 'default.yaml',
                    ],
                ],
            ]
        ]);
        file_put_contents('./default.yaml', "foo: \n  bar: baz");

        $environment = $bootstrap->getEnvironment();
        $yamlAdapter = YamlAdapterFactory::loadFrom($bootstrap->getConfig(), $environment);

        $migration = (object) [
            'filePath' => './tests/migrations/0000000003_add_element_to_map.php',
            'uniqueId' => '0000000003_add_element_to_map'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \AddElementToMap($migration, $args, $bootstrap, $yamlAdapter);
        $migrationInstance->setState();
        $migrationInstance->run(Migrate::ACTION);

        $yamlAdapter->save($migrationInstance->getState());

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals(file_get_contents($environment->yaml->file), "foo:\n  bar: baz\n  0: element1\n");

        unlink('./default.yaml');
    }

    public function testAddWithInterimSync(): void
    {
        $args = new Args([]);

        $bootstrap = Bootstrap::getInstance($args);

        // Seed the config
        $bootstrap->seedConfig([
            'environments' => [
                'default' => [
                    'path' => './tests/migrations',
                    'yaml' => [
                        'file' => 'default.yaml',
                    ],
                ],
            ]
        ]);
        file_put_contents('./default.yaml', "foo: \n  bar: baz");

        $environment = $bootstrap->getEnvironment();
        $yamlAdapter = YamlAdapterFactory::loadFrom($bootstrap->getConfig(), $environment);

        $migration = (object) [
            'filePath' => './tests/migrations/0000000004_add_with_interim_sync.php',
            'uniqueId' => '0000000004_add_with_interim_sync'
        ];

        include_once($migration->filePath);

        $migrationInstance = new \AddWithInterimSync($migration, $args, $bootstrap, $yamlAdapter);
        $migrationInstance->setState();
        $migrationInstance->run(Migrate::ACTION);

        $yamlAdapter->save($migrationInstance->getState());

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals(file_get_contents($environment->yaml->file), "foo:\n  bar:\n    - boo\n    - buzz\n");

        unlink('./default.yaml');
    }

}
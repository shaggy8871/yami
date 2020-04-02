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
        // Seed the config
        Bootstrap::seedConfig([
            'environments' => [
                'default' => [
                    'yamlFile' => './tests/default.yaml',
                    'path' => './tests/migrations',
                ],
            ]
        ]);

        $testMigration = include_once('./tests/migrations/0000000000_test_class.php');
        $migrationInstance = new \TestClass(Migrate::ACTION, (object) ['className' => 'TestClass', 'filePath' => './tests/migrations/0000000000_test_class.php', 'uniqueId' => '0000000000_test_class',], new Args([]));

        $rootNode = $migrationInstance->get('.');

        $this->assertInstanceOf(Node::class, $rootNode);
        $this->assertEquals($migrationInstance->get('.'), new Node(['foo' => 'baz'], '.'));
    }

}
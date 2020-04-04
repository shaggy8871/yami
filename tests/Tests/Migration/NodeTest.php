<?php declare(strict_types=1);

namespace Tests\Migration;

use PHPUnit\Framework\TestCase;
use Yami\Migration\Node;

class NodeTest extends TestCase
{

    public function testGet(): void
    {
        $nodeValue = [
            'foo' => 'bar'
        ];
        $node = new Node($nodeValue, '.');

        $this->assertEquals($node->get(), $nodeValue);
    }

    public function testSet(): void
    {
        $node = new Node([
            'foo' => 'bar'
        ], '.');

        $node->set(['bar' => 'baz']);

        $this->assertEquals($node->get(), ['bar' => 'baz']);
    }

    public function testAddOnMap(): void
    {
        $node = new Node([
            'foo' => 'bar'
        ], '.');

        $node->add(['bar' => 'baz']);

        $this->assertEquals($node->get(), ['foo' => 'bar', 'bar' => 'baz']);
    }

    public function testAddOnArray(): void
    {
        $node = new Node([
            'foobar'
        ], '.');

        $node->add(['barfoo', 'fazboo']);

        $this->assertEquals($node->get(), ['foobar', 'barfoo', 'fazboo']);
    }

    public function testAddOnNonArray(): void
    {
        $this->expectException(\Exception::class);

        $node = new Node('foobar', '.');

        $node->add(['bar' => 'baz']);
    }

    public function testAddOnRoot(): void
    {
        $node = new Node(null, '.');

        $node->add(['bar' => 'baz']);

        $this->assertEquals($node->get(), ['bar' => 'baz']);
    }

    public function testAddArrayOnMap(): void
    {
        $node = new Node([
            'foo' => 'bar'
        ], '.');

        $node->add('element1');

        $this->assertEquals($node->get(), ['foo' => 'bar', 'element1']);
    }

    public function testRemoveOnMap(): void
    {
        $node = new Node([
            'foo' => 'bar'
        ], '.');

        $node->remove('foo');

        $this->assertEquals($node->get(), []);
    }

    public function testRemoveOnArray(): void
    {
        $node = new Node([
            'foobar',
            'barfoo',
            'fazboo'
        ], '.');

        $node->remove([0, 1]);

        $this->assertEquals($node->get(), ['fazboo']);
    }

    public function testRemoveOnScalar(): void
    {
        $this->expectException(\Exception::class);

        $node = new Node('foobar', '.');

        $node->remove('foobar');
    }

    public function testRemoveEmpty(): void
    {
        $node = new Node(['foo' => 'bar'], '.');

        $node->remove('foo');

        $this->assertEmpty($node->get());
    }

    public function testHasOnArray(): void
    {
        $node = new Node([
            'foobar',
            'barfoo',
        ], '.');

        $this->assertTrue($node->has(0));
        $this->assertFalse($node->has(2));
    }

    public function testHasOnMap(): void
    {
        $node = new Node([
            'foo' => 'bar',
            'bar' => 'foo',
        ], '.');

        $this->assertTrue($node->has('foo'));
        $this->assertFalse($node->has('baz'));
    }

    public function testContainsArray(): void
    {
        $node = new Node([
            'foo' => ['bar', 'baz'],
            'bar' => 'foo',
        ], '.');

        $this->assertTrue($node->containsArray('foo'));
        $this->assertFalse($node->containsArray('bar'));
    }

    public function testContainsType(): void
    {
        $node = new Node([
            'foo' => 123,
            'bar' => 'foo',
            'baz' => 12.0,
            'boo' => true,
            'bad' => 'ban'
        ], '.');

        $this->assertTrue($node->containsType('foo', 'integer'));
        $this->assertTrue($node->containsType('bar', 'string'));
        $this->assertTrue($node->containsType('baz', 'float'));
        $this->assertTrue($node->containsType('boo', 'boolean'));
        $this->assertFalse($node->containsType('bad', 'integer'));
    }

    public function testGetSelector(): void
    {
        $nodeValue = [
            'foo' => 'bar'
        ];
        $node = new Node($nodeValue, '.');

        $this->assertEquals($node->getSelector(), '.');
    }

}
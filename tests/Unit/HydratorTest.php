<?php

declare(strict_types=1);

namespace Goat\Hydrator\Tests\Unit;

use Goat\Hydrator\HydratorInterface;
use Goat\Hydrator\HydratorMap;

class HydratorTest extends \PHPUnit_Framework_TestCase
{
    use HydratorTestTrait;

    /**
     * Test basics
     */
    public function testBasicFeatures()
    {
        $hydratorMap = $this->createHydratorMapInstance();
        $hydrator = $hydratorMap->get(HydratedClass::class);

        $test1 = new HydratedClass();
        $this->assertTrue($test1->constructorHasRun);
        $this->assertFalse($test1->constructorHasRunWithData);

        $hydrator->hydrateObject(['foo' => 42, 'bar' => 'test', 'baz' => true], $test1);
        $this->assertSame(42, $test1->getFoo());
        $this->assertSame('test', $test1->getBar());
        $this->assertSame(true, $test1->getBaz());
        $this->assertTrue($test1->constructorHasRun);
        $this->assertFalse($test1->constructorHasRunWithData);

        $test2 = $hydrator->createAndHydrateInstance(['foo' => 666, 'bar' => 'pouet', 'baz' => false], HydratorInterface::CONSTRUCTOR_LATE);
        $this->assertSame(666, $test2->getFoo());
        $this->assertSame('pouet', $test2->getBar());
        $this->assertSame(false, $test2->getBaz());
        $this->assertTrue($test2->constructorHasRun);
        $this->assertTrue($test2->constructorHasRunWithData);

        $test3 = $hydrator->createAndHydrateInstance(['foo' => 118, 'bar' => 'cassoulet', 'baz' => false], HydratorInterface::CONSTRUCTOR_SKIP);
        $this->assertSame(118, $test3->getFoo());
        $this->assertSame('cassoulet', $test3->getBar());
        $this->assertSame(false, $test3->getBaz());
        $this->assertFalse($test3->constructorHasRun);
        $this->assertFalse($test3->constructorHasRunWithData);

        $test3 = $hydrator->createAndHydrateInstance(['foo' => 218, 'bar' => 'maroilles', 'baz' => true], HydratorInterface::CONSTRUCTOR_NORMAL);
        $this->assertSame(218, $test3->getFoo());
        $this->assertSame('maroilles', $test3->getBar());
        $this->assertSame(true, $test3->getBaz());
        $this->assertTrue($test3->constructorHasRun);
        $this->assertFalse($test3->constructorHasRunWithData);

        $values = $hydrator->extractValues($test3);
        $this->assertCount(8, $values);
        $this->assertSame(218, $values['foo']);
        $this->assertSame('maroilles', $values['bar']);
        $this->assertSame(true, $values['baz']);
    }
}

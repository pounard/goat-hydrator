<?php

declare(strict_types=1);

namespace Goat\Hydrator\Tests\Unit;

use Doctrine\Common\Annotations\AnnotationReader;
use GeneratedHydrator\Configuration;
use Goat\Hydrator\HydratorInterface;
use Goat\Hydrator\Configuration\ClassConfiguration;

class HierachicalHydratorTest extends \PHPUnit_Framework_TestCase
{
    use HydratorTestTrait;

    private function createNestingHydratorDefinition() /* : HydratorMap */
    {
        $hydratorMap = $this->createHydratorMapInstance();

        $hydratorMap->addClassConfiguration(new ClassConfiguration(
            HydratedClass::class,
            [
                'someNestedInstance' => HydratedParentClass::class,
            ],
            [],
            HydratorInterface::CONSTRUCTOR_SKIP
        ));

        $hydratorMap->addClassConfiguration(new ClassConfiguration(
            HydratedNestingClass::class,
            [
                'nestedObject1' => HydratedClass::class, 
                'nestedObject2' => HydratedParentClass::class,
            ],
            [],
            HydratorInterface::CONSTRUCTOR_SKIP
        ));

        $hydratorMap->addClassConfiguration(new ClassConfiguration(
            RecursivelyHydratedClass::class,
            ['bar' => RecursivelyHydratedClass::class],
            [],
            HydratorInterface::CONSTRUCTOR_SKIP
        ));

        return $hydratorMap;
    }

    /**
     * Test object nesting hydration up to 3 levels of hydration
     */
    public function testNesting()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'ownProperty1' => 1,
            'ownProperty2' => 3,
            'nestedObject1.foo' => 5,
            'nestedObject1.bar' => 7,
            'nestedObject1.someNestedInstance.miaw' => 17,
            'nestedObject2.miaw' => 11,
        ];

        /** @var \Goat\Tests\Hydrator\HydratedNestingClass $nesting1 */
        $nesting1 = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedNestingClass::class, $nesting1);
        $this->assertSame(1, $nesting1->getOwnProperty1());
        $this->assertSame(3, $nesting1->getOwnProperty2());
        $this->assertInstanceOf(HydratedClass::class, $nesting1->getNestedObject1());
        $this->assertFalse($nesting1->getNestedObject1()->constructorHasRun);
        $this->assertSame(5, $nesting1->getNestedObject1()->getFoo());
        $this->assertSame(7, $nesting1->getNestedObject1()->getBar());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject2());
        $this->assertSame(11, $nesting1->getNestedObject2()->getMiaw());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject1()->getSomeNestedInstance());
        $this->assertSame(17, $nesting1->getNestedObject1()->getSomeNestedInstance()->getMiaw());

        $nesting2 = new HydratedNestingClass();
        $hydrator->hydrateObject($values, $nesting2);
        $this->assertInstanceOf(HydratedNestingClass::class, $nesting2);
        $this->assertSame(1, $nesting2->getOwnProperty1());
        $this->assertSame(3, $nesting2->getOwnProperty2());
        $this->assertInstanceOf(HydratedClass::class, $nesting2->getNestedObject1());
        $this->assertFalse($nesting1->getNestedObject1()->constructorHasRun);
        $this->assertSame(5, $nesting2->getNestedObject1()->getFoo());
        $this->assertSame(7, $nesting2->getNestedObject1()->getBar());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting2->getNestedObject2());
        $this->assertSame(11, $nesting2->getNestedObject2()->getMiaw());
        $this->assertInstanceOf(HydratedParentClass::class, $nesting1->getNestedObject1()->getSomeNestedInstance());
        $this->assertSame(17, $nesting1->getNestedObject1()->getSomeNestedInstance()->getMiaw());
    }

    /**
     * Test recursive nesting with multiple values
     */
    public function testDeepNesting()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();

        $values = [
            [
                'foo' => 11,
                'bar.foo' => 12,
                'bar.bar.foo' => 13,
                'bar.bar.bar.foo' => 14,
            ],
            [
                'foo' => 21,
                'bar.foo' => 22,
                'bar.bar.foo' => 23,
                'bar.bar.bar.foo' => 24,
            ],
        ];

        $items = [];
        $hydrator = $hydratorMap->get(RecursivelyHydratedClass::class);
        foreach ($values as $value) {
            $items[] = $hydrator->createAndHydrateInstance($value);
        }

        $this->assertCount(2, $items);

        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($one = $items[0]));
        $this->assertSame(11, $one->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($two = $one->getBar()));
        $this->assertSame(12, $two->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($three = $two->getBar()));
        $this->assertSame(13, $three->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($four = $three->getBar()));
        $this->assertSame(14, $four->getFoo());

        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($one = $items[1]));
        $this->assertSame(21, $one->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($two = $one->getBar()));
        $this->assertSame(22, $two->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($three = $two->getBar()));
        $this->assertSame(23, $three->getFoo());
        $this->assertInstanceOf(RecursivelyHydratedClass::class , ($four = $three->getBar()));
        $this->assertSame(24, $four->getFoo());
    }

    public function testExistingNullPropertyIsIgnored()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'nestedObject1' => null,
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedClass::class, $object->getNestedObject1());
    }

    public function testExistingEmptyArrayPropertyIsIgnored()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'nestedObject1' => [],
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedClass::class, $object->getNestedObject1());
    }

    public function testExistingEmptyStringPropertyIsIgnored()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'nestedObject1' => '',
            'nestedObject1.foo' => 5,
        ];

        $object = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedClass::class, $object->getNestedObject1());
    }

    public function testExistingPropertyRaiseErrors()
    {
        $hydratorMap = $this->createNestingHydratorDefinition();
        $hydrator = $hydratorMap->get(HydratedNestingClass::class);

        $values = [
            'nestedObject1' => ['bar' => 'baz'],
            'nestedObject1.foo' => 5,
        ];

        $this->expectException(\InvalidArgumentException::class);
        $hydrator->createAndHydrateInstance($values);
    }

    public function testPropertyBlacklist()
    {
        $this->markTestIncomplete("Implement me");
    }

    /**
     * Test object nested hydration class name discovery using annotation works
     */
    public function testNestedAnnotedDiscovery()
    {
        // @todo I am sorry, I need to fix this
        $this->markTestIncomplete("this needs to be implemented properly");

        $hydratorMap = $this->createHydratorMapInstance();

        //AnnotationRegistry::registerAutoloadNamespace("MyProject\Annotations", "/path/to/myproject/src");
        //AnnotationRegistry::registerLoader('class_exists');
        $annotationReader = new AnnotationReader();
        $configuration = new Configuration();
        $configuration->setAnnotationReader($annotationReader);

        $hydrator = $hydratorMap->get(HydratedClass::class);

        $values = [
            'annotedNestedInstance.miaw' => 42,
        ];

        /** @var \Goat\Tests\Hydrator\HydratedClass $instance */
        $instance = $hydrator->createAndHydrateInstance($values);
        $this->assertInstanceOf(HydratedParentClass::class, $instance->getAnnotedNestedInstance());
        $this->assertSame(42, $instance->getAnnotedNestedInstance()->getMiaw());
    }
}

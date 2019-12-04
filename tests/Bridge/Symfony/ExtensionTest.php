<?php

declare(strict_types=1);

namespace Goat\Hydrator\Tests\Bridge\Symfony;

use Goat\Hydrator\Bridge\Symfony\GoatHydratorExtension;
use Goat\Hydrator\Tests\Unit\HydratedClass;
use Goat\Hydrator\Tests\Unit\HydratedParentClass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\Yaml\Parser;

class ExtensionTest extends \PHPUnit_Framework_TestCase
{
    private function createTemporaryDirectory()
    {
        $directory =  \sys_get_temp_dir().'/'.\uniqid('test-');

        if (!\mkdir($directory, 0777, true)) {
            throw new \Exception(\sprintf("Could not create cache directory: %s", $directory));
        }

        return $directory;
    }

    private function parseYaml($yaml)
    {
        return (new Parser())->parse($yaml);
    }

    private function getContainer()
    {
        return new ContainerBuilder(new ParameterBag(array(
            'kernel.debug'       => false,
            'kernel.bundles'     => array(),
            'kernel.cache_dir'   => $this->createTemporaryDirectory(),
            'kernel.environment' => 'test',
            'kernel.root_dir'    => __DIR__.'/../../', // src dir
            'kernel.project_dir' => __DIR__.'/../../', // src dir
        )));
    }

    public function testEmptyExtension()
    {
        $extension = new GoatHydratorExtension();
        $extension->load([[]], $container = $this->getContainer());

        $this->assertArrayHasKey('goat.hydrator_map', $container->getDefinitions());
    }

    public function testPropertyConfigurationNonExistingClass()
    {
        $this->expectException(\InvalidArgumentException::class);

        $input = <<<EOT
classes:
    \NonExistingClass:
        properties:
            someNestedInstance: \Goat\Hydrator\Tests\Unit\HydratedParentClass
EOT;

        $extension = new GoatHydratorExtension();
        $extension->load([$this->parseYaml($input)], $this->getContainer());
    }

    public function testPropertyConfigurationNonExistingProperty()
    {
        $this->expectException(\InvalidArgumentException::class);

        $input = <<<EOT
classes:
    Goat\Hydrator\Tests\Unit\HydratedClass:
        properties:
            non_existing_property: \Goat\Hydrator\Tests\Unit\HydratedParentClass
EOT;

        $extension = new GoatHydratorExtension();
        $extension->load([$this->parseYaml($input)], $this->getContainer());
    }

    public function testPropertyConfigurationUsingClass()
    {
        $input = <<<EOT
classes:
    Goat\Hydrator\Tests\Unit\HydratedClass:
        properties:
            someNestedInstance: \Goat\Hydrator\Tests\Unit\HydratedParentClass
EOT;

        $extension = new GoatHydratorExtension();
        $extension->load([$this->parseYaml($input)], $container = $this->getContainer());

        $container->getDefinition('goat.hydrator_map')->setPublic(true);
        $container->compile();

        /** @var \Goat\Hydrator\HydratorMap $hydratorMap */
        $hydratorMap = $container->get('goat.hydrator_map');
        $properties = $hydratorMap->getClassConfiguration(HydratedClass::class)->getPropertyMap();
        $this->assertSame(HydratedParentClass::class, $properties['someNestedInstance']);
    }

    /*
    public function testPropertyConfigurationUsingScalar()
    {
        
    }

    public function testPropertyConfigurationAsNull()
    {
        
    }
     */
}

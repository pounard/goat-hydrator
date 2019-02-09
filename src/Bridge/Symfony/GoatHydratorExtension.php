<?php

namespace Goat\Hydrator\Bridge\Symfony;

use GeneratedHydrator\Strategy\ClassNamingStrategy;
use GeneratedHydrator\Strategy\HashNamingStrategy;
use Goat\Hydrator\Configuration\ClassConfiguration;
use Goat\Hydrator\Configuration\ClassConfigurator;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * @codeCoverageIgnore
 */
final class GoatHydratorExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($configuration, $configs);

        $loader = new YamlFileLoader($container, new FileLocator(__DIR__.'/Resources/config'));
        $loader->load('services.yml');

        $mapDefinition = $container->getDefinition('goat.hydrator_map');
        if (empty($config['blacklist'])) {
            $blacklist = [];
        } else {
            $blacklist = $config['blacklist'];
        }

        if (isset($config['classes'])) {
            $configurator = new ClassConfigurator($blacklist);
            foreach ($config['classes'] as $class => $configuration) {
                $serviceId = 'goat_hydrator_'.\str_replace('\\', '_', $class);
                $definition = $this->parseClassConfiguration($class, $configuration, $configurator);
                $container->setDefinition($serviceId, $definition);
                $mapDefinition->addMethodCall('addClassConfiguration', [new Reference($serviceId)]);
            }
        }

        if ($blacklist) {
            $mapDefinition->setArgument(1, $blacklist);
        }

        $this->createGeneratedHydratorConfiguration($container, $config);
    }

    /**
     * {@inheritdoc}
     */
    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new GoatHydratorConfiguration();
    }

    /**
     * From the bundle configuration, create generated hydrator configuration
     */
    private function createGeneratedHydratorConfiguration(ContainerBuilder $container, array $config)
    {
        $definition = $container->getDefinition('goat.generated_hydrator.configuration');

        if ($directory = $config['target_dir'] ?? null) {
            if ('/' !== $directory[0]) {
                $directory = "%kernel.project_dir%/".$directory;
            }
            $definition->addMethodCall('setGeneratedClassesTargetDir', [$directory]);
        }
        if (isset($config['target_namespace'])) {
            $definition->addMethodCall('setGeneratedClassesNamespace', [$config['target_namespace']]);
        }
        if (isset($config['target_namespace_prefix'])) {
            $definition->addMethodCall('setNamespacePrefix', [$config['target_namespace_prefix']]);
        }

        if (isset($config['naming_strategy'])) {
            $strategyServiceId = 'goat_hydrator.generated_config_name.strategy';

            switch ($config['naming_strategy']) {

                case 'class':
                    $container->setDefinition(
                        $strategyServiceId,
                        (new Definition())->setClass(ClassNamingStrategy::class)->setPublic(false)
                    );
                    break;

                case 'hash':
                    $container->setDefinition(
                        $strategyServiceId,
                        (new Definition())->setClass(HashNamingStrategy::class)->setPublic(false)
                    );
                    break;
            }

            $definition->addMethodCall('setNamingStrategy', [new Reference($strategyServiceId)]);
        }
    }

    /**
     * Create class configuration service definition
     */
    private function parseClassConfiguration($class, $input, ClassConfigurator $configurator)
    {
        if (!\class_exists($class)) {
            throw new \InvalidArgumentException(\sprintf(
                "Invalid hydrator configuration for class '%s': class does not exist",
                $class
            ));
        }

        $definition = new Definition();
        $definition->setClass(ClassConfiguration::class);

        $configuration = $configurator->configureClass($class);
        $properties = $configuration->getPropertyMap();

        // Parse user-set properties as overrides for the automatic configuration.
        $reflexionClass = new \ReflectionClass($class);
        if (isset($input['properties'])) {
            foreach ($input['properties'] as $name => $type) {

                $found = false;
                $parent = $reflexionClass;
                do {
                    if ($parent->hasProperty($name)) {
                        $found = true;
                    }
                } while (!$found && ($parent = $parent->getParentClass()));

                if (!$found) {
                    throw new \InvalidArgumentException(\sprintf(
                        "Invalid hydrator configuration for class '%s': property '%s' does not exist",
                        $class, $name
                    ));
                }

                if (null === $type || false === $type) {
                    unset($properties[$name]); // Force exclusion
                } else {
                    if (!\class_exists($type)) {
                        throw new \InvalidArgumentException(\sprintf(
                            "Invalid hydrator configuration for class '%s': property '%s' must be null, a scalar type, or an existing class",
                            $class, $name
                        ));
                    }
                    $properties[$name] = $type;
                }
            }
        }

        $definition->setArguments([
            $class,
            $properties,
            $configuration->getClassDependencies(),
            $configuration->getConstructorMode(),
        ]);

        return $definition;
    }
}

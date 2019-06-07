<?php

namespace Goat\Hydrator;

use Goat\Hydrator\Configuration\ClassConfiguration;
use GeneratedHydrator\Configuration;

final class HydratorMap
{
    private $classBlacklist = [];
    private $configurations = [];
    private $customHydrators = [];
    private $generatedHydratorConfiguration;
    private $realHydrators = [];

    /**
     * Defalut constructor
     *
     * @param string $cacheDir
     */
    public function __construct(Configuration $generatedHydratorConfiguration, array $classBlacklist = [])
    {
        $this->classBlacklist = $classBlacklist;
        $this->generatedHydratorConfiguration = $generatedHydratorConfiguration;
    }

    /**
     * Is this class supported
     */
    public function supportsClass($class)
    {
        return !\in_array($class, $this->classBlacklist);
    }

    /**
     * Add a class configuration
     */
    public function addClassConfiguration(ClassConfiguration $configuration)
    {
        $this->configurations[$configuration->getClassName()] = $configuration;
    }

    /**
     * Get properties to hydrate for class
     *
     * @param string $class
     *   Class name from which to find properties, it must be a fully qualified one
     *
     * @return ClassConfiguration
     */
    public function getClassConfiguration($class)
    {
        $class = ClassConfiguration::normalizeClassName($class);

        if (!isset($this->configurations[$class])) {
            $this->configurations[$class] = new ClassConfiguration($class);
        }

        return $this->configurations[$class];
    }

    /**
     * Set a custom type hydrator
     */
    public function addCustomHydrator($typeOrClass, callable $callback)
    {
        $this->customHydrators[$typeOrClass] = $callback;
    }

    /**
     * Get custom hydrator for given type or class
     *
     * @return null|callable
     *
     * @internal
     */
    public function getCustomHydratorFor($typeOrClass)
    {
        $typeOrClass = ClassConfiguration::normalizeClassName($typeOrClass);

        if (isset($this->customHydrators[$typeOrClass])) {
            return $this->customHydrators[$typeOrClass];
        }
    }

    /**
     * Create an hydrator instance using the default hydrator class
     *
     * @param string $class
     *   Class name
     *
     * @return HydratorInterface
     */
    private function createHydrator($class)
    {
        if (isset($this->realHydrators[$class])) {
            return $this->realHydrators[$class];
        }

        $configuration = clone $this->generatedHydratorConfiguration;
        $configuration->setHydratedClassName($class);

        return $this->realHydrators[$class] = new GeneratedHydrator($class, $configuration);
    }

    /**
     * Get hydrator for class or identifier
     *
     * @param string $class
     *   Either a class name or a class alias
     *
     * @return HydratorInterface
     *
     * @internal
     */
    public function getRealHydrator($class)
    {
        if (!$this->supportsClass($class)) {
            throw new \InvalidArgumentException(\sprintf("Class '%s' is explicitely blacklisted therefore cannot be hydrated.", $class));
        }

        return $this->createHydrator($class);
    }

    /**
     * Get hydrator for class or identifier
     *
     * @param string $class
     *   Either a class name or a class alias
     * @param string $separator
     *   Separator for the hierarchical hydrator
     *
     * @return HydratorInterface
     *   Returned instance will not be shared
     */
    public function get($class, $separator = null)
    {
        if (!$this->supportsClass($class)) {
            throw new \InvalidArgumentException(\sprintf("Class '%s' is explicitely blacklisted therefore cannot be hydrated.", $class));
        }

        return new HierarchicalHydrator($this->getClassConfiguration($class), $this, $separator);
    }
}

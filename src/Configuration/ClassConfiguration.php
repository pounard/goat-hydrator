<?php

namespace Goat\Hydrator\Configuration;

use Goat\Hydrator\HydratorInterface;

class ClassConfiguration
{
    private $class;
    private $constructor;
    private $dependencies = [];
    private $properties = [];
    private $propertyBlacklist = [];

    public static function normalizeClassName($className)
    {
        if ('\\' === $className[0]) {
            $className = \ltrim($className, '\\');
        }

        return $className;
    }

    /**
     * Default constructor
     */
    public function __construct($class, array $properties = [], array $dependencies = [], $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        $this->class = self::normalizeClassName($class);
        $this->constructor = $constructor;
        if ($dependencies) {
            $this->dependencies = \array_map([self::class, 'normalizeClassName'], $dependencies);
        }
        if ($properties) {
            foreach ($properties as $property => $type) {
                if (null === $type || false === $type) {
                    $this->propertyBlacklist[$property] = true;
                } else {
                    $this->properties[$property] = self::normalizeClassName($type);
                }
            }
        }
    }

    /**
     * Get class name
     */
    public function getClassName()
    {
        return $this->class;
    }

    /**
     * Get constructor mode
     */
    public function getConstructorMode()
    {
        return $this->constructor;
    }

    /**
     * Get dependencies as class names
     */
    public function getClassDependencies()
    {
        return $this->dependencies;
    }

    /**
     * Is property blacklisted
     */
    public function isPropertyBlacklisted($property)
    {
        return isset($this->propertyBlacklist[$property]);
    }

    /**
     * Get property map
     */
    public function getPropertyMap()
    {
        return $this->properties;
    }
}

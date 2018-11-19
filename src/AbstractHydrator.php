<?php

namespace Goat\Hydrator;

abstract class AbstractHydrator implements HydratorInterface
{
    protected $className;
    private $constructorIsPrivate = false;
    private $hasConstructor = false;
    private $reflectionClass;

    /**
     * Default constructor
     *
     * @param string $className
     */
    public function __construct($className)
    {
        $this->className = $className;

        if (!\class_exists($this->className)) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf("'%s' class does not exists", $this->className));
            // @codeCoverageIgnoreEnd
        }

        $this->reflectionClass = new \ReflectionClass($this->className);
        if ($this->hasConstructor = $this->reflectionClass->hasMethod('__construct')) {
            $this->constructorIsPrivate = $this->reflectionClass->getMethod('__construct')->isPrivate();
        }
    }

    /**
     * Hydrate the given object with the given values
     */
    abstract protected function hydrateInstance($values, $object);

    /**
     * Extract values from the given instance
     */
    abstract protected function extractFromInstance($object);

    /**
     * Create object instance without constructor
     *
     * @return mixed
     */
    private function createInstanceWithoutConstructor()
    {
        if (!$this->reflectionClass) {
            $this->reflectionClass = new \ReflectionClass($this->className);
        }

        return $this->reflectionClass->newInstanceWithoutConstructor();
    }

    /**
     * {@inheritdoc}
     */
    final public function createAndHydrateInstance(array $values, $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        if ($this->constructorIsPrivate ||
            HydratorInterface::CONSTRUCTOR_SKIP === $constructor ||
            HydratorInterface::CONSTRUCTOR_LATE === $constructor
        ) {
            $object = $this->createInstanceWithoutConstructor();
        } else {
            $object = new $this->className();
        }

        $this->hydrateInstance($values, $object);

        if ($this->hasConstructor && !$this->constructorIsPrivate && HydratorInterface::CONSTRUCTOR_LATE === $constructor) {
            // @todo How about constructor arguments?
            $object->__construct();
        }

        return $object;
    }

    /**
     * {@inheritdoc}
     */
    final public function hydrateObject(array $values, $object)
    {
        if (!$object instanceof $this->className) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf("given object is not a '%s' instance", $this->className));
            // @codeCoverageIgnoreEnd
        }

        $this->hydrateInstance($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    final public function extractValues($object)
    {
        if (!$object instanceof $this->className) {
            // @codeCoverageIgnoreStart
            throw new \InvalidArgumentException(\sprintf("given object is not a '%s' instance", $this->className));
            // @codeCoverageIgnoreEnd
        }

        return $this->extractFromInstance($object);
    }
}

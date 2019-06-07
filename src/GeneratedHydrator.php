<?php

namespace Goat\Hydrator;

use GeneratedHydrator\Configuration;

/**
 * Hydrator implementation using generated hydrator
 */
final class GeneratedHydrator extends AbstractHydrator
{
    private $configuration;
    private $hydrator;

    /**
     * Default constructor
     *
     * @param string $className
     * @param string $cacheDir
     */
    public function __construct($className, Configuration $configuration = null)
    {
        parent::__construct($className);

        $this->configuration = $configuration ? $configuration : new Configuration();

        $hydratorName = $this->configuration->createFactory()->getHydratorClass();
        $this->hydrator = new $hydratorName();
    }

    /**
     * {@inheritdoc}
     */
    protected function hydrateInstance($values, $object)
    {
        $this->hydrator->hydrate($values, $object);
    }

    /**
     * {@inheritdoc}
     */
    protected function extractFromInstance($object)
    {
        return $this->hydrator->extract($object);
    }
}

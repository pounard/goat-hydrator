<?php

namespace Goat\Hydrator;

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
    public function __construct($className, $cacheDir = null)
    {
        parent::__construct($className);

        $this->configuration = new \GeneratedHydrator\Configuration($className);

        if (!$cacheDir) {
            $cacheDir = \sys_get_temp_dir().'/goat-hydrator';
        }
        if (!\is_dir($cacheDir) && !@\mkdir($cacheDir)) { // Attempt directory creation
            throw new \InvalidArgumentException(\sprintf("'%s': could not create directory", $cacheDir));
        }
        $this->configuration->setGeneratedClassesTargetDir($cacheDir);

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

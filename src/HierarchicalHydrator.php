<?php

namespace Goat\Hydrator;

use Goat\Hydrator\Configuration\ClassConfiguration;

/**
 * This hydrator must not be re-used, it must run once for a single query
 * because it keep an internal state cache that represents the database
 * result form.
 */
final class HierarchicalHydrator implements HydratorInterface
{
    const DEFAULT_SEPARATOR = '.';

    private $allowCache = true;
    private $class;
    private $debug = true;
    private $groupCache = [];
    private $hydratorMap;
    private $hydrators = [];
    private $realHydrator;
    private $separator;

    /**
     * Default constructor
     */
    public function __construct(ClassConfiguration $class, HydratorMap $hydratorMap, $separator = null, $allowCache = true, $debug = true)
    {
        $this->allowCache = (bool)$allowCache;
        $this->class = $class;
        $this->debug = (bool)$debug;
        $this->hydratorMap = $hydratorMap;
        $this->realHydrator = $hydratorMap->getRealHydrator($class->getClassName());
        $this->separator = isset($separator) ? $separator : self::DEFAULT_SEPARATOR;
    }

    /**
     * Aggregate properties under the given group
     *
     * @param string $group
     *   Group name, key values name prefixes
     * @param mixed[] $values
     *   Values being hydrated
     *
     * @return mixed[]
     */
    private function aggregatePropertiesOf($depth, $group, array $values)
    {
        $cacheId = $depth.$group;

        // In various benchmarks, values grouping is the most expensive
        // operation due to numerous string manipulations, this cache
        // complexity does reduce drastically this phase.
        if (!isset($this->groupCache[$cacheId])) {
            // Fetch and cache value array key list, so we won't do this for
            // each hydrated object - this is particulary efficient when
            // hydrating object lists incomming from the same SQL query or
            // same result structure stream.
            $this->groupCache[$cacheId] = [];

            $first = $group[0];
            $length = \strlen($group) + \strlen($this->separator);
            $reference = $group.$this->separator;

            foreach ($values as $key => $value) {
                if ($key[0] === $first && \strlen($key) > $length && \substr($key, 0, $length) === $reference) {
                    $this->groupCache[$cacheId][$key] = \substr($key, $length);
                }
            }
        }

        $ret = [];
        $keys = $this->groupCache[$cacheId];

        foreach ($keys as $key => $property) {
            $ret[$property] = $values[$key];
        }

        return $ret;
    }

    /**
     * Create nested objects and set them in the new returned dataset
     */
    private function aggregateNestedProperties(array $values, $class, $depth = 1)
    {
        $configuration = $this->hydratorMap->getClassConfiguration($class);

        foreach ($configuration->getPropertyMap() as $property => $childClass) {
            $exists = \array_key_exists($property, $values);

            // Custom property/class hydration overrides any other mecanism.
            if ($customHydrator = $this->hydratorMap->getCustomHydratorFor($childClass)) {
                if ($exists) {
                    $values[$property] = \call_user_func($customHydrator, $values[$property]);
                }
                continue;
            }

            // We can't let the hydrator loose any data, but having a conflict
            // here shows the original data we want to hydrate is inconsistent,
            // break and warn the developer.
            if ($exists) {
                if ($this->debug) {
                    throw new \InvalidArgumentException(\sprintf(
                        "nested property '%s' with class '%s' already has a value: '%s'",
                        $property, $childClass, $values[$property]
                    ));
                }
                continue; // Do not let production explode.
            }

            if ($nestedValues = $this->aggregatePropertiesOf($depth, $property, $values)) {
                $values[$property] = $this
                    ->hydratorMap
                    ->getRealHydrator($childClass)
                    ->createAndHydrateInstance(
                        $this->aggregateNestedProperties($nestedValues, $childClass, $depth + 1),
                        $configuration->getConstructorMode()
                    )
                ;
            }
        }

        return $values;
    }

    /**
     * {@inheritdoc}
     */
    public function createAndHydrateInstance(array $values, $constructor = HydratorInterface::CONSTRUCTOR_LATE)
    {
        return $this
            ->realHydrator
            ->createAndHydrateInstance(
                $this->aggregateNestedProperties($values, $this->class->getClassName()),
                $constructor
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function hydrateObject(array $values, $object)
    {
        return $this
            ->realHydrator
            ->hydrateObject(
                $this->aggregateNestedProperties($values, $this->class->getClassName()),
                $object
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    public function extractValues($object)
    {
        return $this->realHydrator->extractValues($object);
    }
}

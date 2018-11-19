<?php

namespace Goat\Hydrator;

interface HydratorInterface
{
    const CONSTRUCTOR_NORMAL = 0;
    const CONSTRUCTOR_SKIP = 1;
    const CONSTRUCTOR_LATE = 2;

    /**
     * Create object instance then hydrate it
     *
     * @param array $values
     * @param string $class
     *
     * @return object
     *   The new instance
     */
    public function createAndHydrateInstance(array $values, $constructor = HydratorInterface::CONSTRUCTOR_LATE);

    /**
     * Hydrate object instance in place
     *
     * @param array $values
     * @param object $object
     */
    public function hydrateObject(array $values, $object);

    /**
     * Extract values from an object
     *
     * @param object $object
     *
     * @return mixed[]
     */
    public function extractValues($object);
}

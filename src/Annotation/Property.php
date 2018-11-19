<?php

declare(strict_types=1);

namespace Goat\Hydrator\Annotation;

/**
 * @Annotation
 * @Target({"PROPERTY"})
 * @Attributes({
 *   @Attribute("class", type = "string", required = true),
 * })
 */
class Property
{
    private $className;

    /**
     * Default constructor
     */
    public function __construct(array $values)
    {
        $this->className = ($values['class'] ?: '');
    }

    /**
     * Get class name
     */
    public function getClassName()
    {
        return $this->className;
    }
}

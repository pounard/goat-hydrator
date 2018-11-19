<?php

namespace Goat\Hydrator\Configuration;

use Doctrine\Common\Annotations\Reader;
use Goat\Hydrator\Annotation\Property;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractorInterface;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;

/**
 * Class configuration parses a class using all information available
 * (annotations and property info) and uses it to build a graph of
 * dependencies.
 *
 * It must not be used at runtime, it wil be extremely CPU consuming:
 * it has been written as to be used at cache warmup phase.
 */
class ClassConfigurator
{
    private $annotationsReader;
    private $classBlacklist = [];
    private $configurations = [];
    private $propertyClassMap = [];
    private $propertyInfoExtractor;

    /**
     * Default constructor
     */
    public function __construct(array $classBlacklist = [])
    {
        $this->classBlacklist = $classBlacklist;
        // Attempt to auto configure as possible this instance,
        if (\class_exists(PropertyInfoExtractor::class)) {
            $this->propertyInfoExtractor = self::createPropertyInfoExtractor();
        }
    }

    /**
     * Creates a default property info extractor
     *
     * Beware this is a non-cached implementation per default, and might come
     * with a serious performance penalty.
     *
     * If you are using a framework, inject a cached one using the
     * setPropertyInfoReader() method instead.
     *
     * @return PropertyInfoExtractorInterface
     */
    static public function createPropertyInfoExtractor()
    {
        $reflexionExtractor = new ReflectionExtractor();
        $phpDocExtractor = new PhpDocExtractor();

        return new PropertyInfoExtractor([$reflexionExtractor], [$reflexionExtractor, $phpDocExtractor]);
    }

    /**
     * Set annotation reader
     */
    public function setAnnotationReader(Reader $annotationReader)
    {
        $this->annotationsReader = $annotationReader;
    }

    /**
     * Set property info extractor
     */
    public function setPropertyInfoReader(PropertyInfoExtractorInterface $propertyInfoExtractor)
    {
        $this->propertyInfoExtractor = $propertyInfoExtractor;
    }

    /**
     * Dynamically lookup class properties to determine their type
     *
     * @param string $className
     *   Target class name
     *
     * @return array
     *   Keys are property names, values are class names
     */
    private function dynamicPropertiesLookup($class)
    {
        $ret = [];

        // Attempt a pass using annotation reader - annotation are not subject
        // to blacklist because it's a strict user defined requirement.
        if ($this->annotationsReader) {
            $refClass = new \ReflectionClass($class);
            /** @var \ReflectionProperty $refProperty */
            foreach ($refClass->getProperties() as $refProperty) {
                $annotation = $this->annotationsReader->getPropertyAnnotation($refProperty, Property::class);
                if ($annotation instanceof Property) {
                    $ret[$refProperty->getName()] = $annotation->getClassName();
                }
            }
        }

        // Use Symfony's property info extractor if available, apply class
        // blacklist in automatically found class names.
        if ($this->propertyInfoExtractor) {
            if ($properties = $this->propertyInfoExtractor->getProperties($class)) {
                foreach ($properties as $property) {

                    // Do not override annotation discovery
                    if (isset($ret[$property])) {
                        continue;
                    }

                    if ($types = $this->propertyInfoExtractor->getTypes($class, $property)) {
                        foreach ($types as $type) {
                            if (($propertyClassName = $type->getClassName()) &&
                                !\in_array($propertyClassName, $this->classBlacklist)
                            ) {
                                $ret[$property] = $propertyClassName;
                                break; // Proceed with next property
                            }
                        }
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * Configure class hydration
     *
     * @param string $class
     *
     * @return \Goat\Hydrator\Configuration\ClassConfiguration
     */
    public function configureClass($class)
    {
        // @todo
        //   - constructor mode
        //   - dependencies
        return new ClassConfiguration($class, $this->dynamicPropertiesLookup($class));
    }
}

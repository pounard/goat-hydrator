# Goat hierachical hydrator

Advanced object hydrator based upon https://github.com/Ocramius/GeneratedHydrator
supporting nested object hydration.

Optionnaly Ocramius/GeneratedHydrator can be seamlessly replaced by
https://github.com/makinacorpus/generated-hydrator for PHP 5.6 compatibility.

This package provides a Symfony 3.4 and 4.x compatible bundle.


# Get started

For efficiently hydrating objects, it may benefit from an offline preparation
phase that introspects target object classes for later hydration. Using Symfony
this preparation phase is done while compiling the container.


## Test case scenario

Along this documentation, we will work on this scenario, we have three classes
defined as such:

```php
class A
{
    /**
     * @var B
     */
    private $b;

    public function getB(): ?B
    {
        return $this->b;
    }
}

class B
{
    private $bar;

    /**
     * @var C
     */
    private $c;

    public function getBar(): string
    {
        return $this->bar ?? '';
    }

    public function getC(): ?C
    {
        return $this->c;
    }
}

class C
{
    private $foo;

    public function getFoo(): string
    {
        return $this->foo ?? '';
    }
}
```


## Standalone

### Configuration

Simple configuration exemple, more documentation will come later:

```php
$hydratorMap = new HydratorMap(\sys_get_temp_dir().'/hydrator/cache');

// Register the A class
$hydratorMap->addClassConfiguration(new ClassConfiguration(
    A::class,
    [
        'b' => B::class,
    ],
    [],
    HydratorInterface::CONSTRUCTOR_SKIP
));

// Register the B class
$hydratorMap->addClassConfiguration(new ClassConfiguration(
    C::class,
    [
        'c' => C::class,
    ],
    [],
    HydratorInterface::CONSTRUCTOR_SKIP
));
```


### Usage

Usage is trivial:

```php

$separator = '__';

$values = [
    'b__bar' => "Hello, "
    'b__c__foo' => "World !",
];

$hydrator = $hydratorMap->get(A::class);
$a = $hydrator->createAndHydrateInstance($values);
```

And now, calling `print $a->getB()->getBar() . $a->getB()->getC()->getFoo();` should display `Hello, World !`;


## Symfony

### Configuration

Add the bundle in your `bundles.php` file (there is no flex recipe yet):

```php
return [
    // ...
    Goat\Hydrator\Bridge\Symfony\GoatHydratorBundle::class => ['all' => true],
];
```

Use the `goat_hydrator` configuration key:

```yaml
goat_hydrator:

    # Blacklist prevents some classes from being registered as property to
    # hydrate within automatically found properties. These arethe properties
    # target types.
    blacklist:
        - DateTime
        - DateTimeImmutable
        - DateTimeInterface

    classes:

        # Class that are not registered here will not be hierachically
        # hydrated, you MUST register every class you'll need to hydrate.
        App\Domain\Foo:

            # Contructor type, can be "none", "normal" or "late"
            constructor: none

            # Each property defined here will be forcefully hydrated to the
            # given value, which should be an existing class.
            properties:
                role: App\Domain\Role

                # If you encounter issues due to property info component
                # finding invalid or too greedy types, you can disable a
                # property hydration by setting its value to null or false.
                some_other_property: false
```


### Usage

Usage is the same as above, but for convenience you can inject the HydratorMap class
into your controllers or services.


## Behavior

Each time you call `HydratorMap::get()` a new instance will be generated: if you
iterate over, for example, an SQL result set, always use the same instance: there
is caching happening each instance making it faster.


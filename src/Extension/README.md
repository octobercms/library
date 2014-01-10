## Rain Extensions

Adds the ability for classes to have *private traits*, also known as Behaviors. These are similar to native Traits except they can have private/protected methods and properties along with the own constructor.

The originating object is passed always as the first parameter to the constructor.

### Usage example

**Behavior / Extension class**

```php
<?php namespace MyNamespace\Behaviors;

class NinjaTurtle extends \October\Rain\Extension\ExtensionBase
{
    /**
     * @var Reference to the extended object.
     */
    protected $parentObj;

    /**
     * Constructor
     */
    public function __construct($object)
    {
        $this->parentObj = $object;
    }

    public function doSomethingCool()
    {
        return "Cowabunga!!!";
    }
}
```

**Using the extension**

This `PizzaShop` class will implement the `NinjaTurtle` behavior and then call it's `doSomethingCool` method.

```php
<?php namespace MyNamespace;

class PizzaShop extends \October\Rain\Extension\ExtendableBase
{

    /**
     * Implement the NinjaTurtle behavior
     */
    public $implement = [
        'MyNamespace.Behaviors.NinjaTurtle'
    ];

    /**
     * Main constructor does not conflict with Behavior constructor
     */
    public function __construct()
    {
        // Returns "Cowabunga!!!"
        echo $this->doSomethingCool();
    }
}

```

**Make a class extensible without extending**

In some cases you may not wish to extend the `ExtensionBase` or `ExtendableBase` classes, due to other needs. So you can use the traits instead, although obviously the behavior methods will not be available to the parent class.

See `ExtensionTrait` and `ExtendableTrait`.
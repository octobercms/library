## Rain Extensions

Adds the ability for classes to have *private traits*, also known as Behaviors. These are similar to native Traits except they can have private/protected methods and properties along with their own constructor.

The originating object is always passed as the first parameter to the Behavior's constructor.

### Usage example

**Behavior / Extension class**

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

**Using the extension**

This `PizzaShop` class will implement the `NinjaTurtle` behavior and then call its `doSomethingCool` method.

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
            // Initializes the behaviors
            parent::__construct();

            // Prints "Cowabunga!!!"
            echo $this->doSomethingCool();
        }
    }

### Constructor extension

Any class that uses the `ExtendableBase` or `ExtendableTrait` can have its constructor extended. For example:

    /**
     * Extend the Pizza Shop to include the Master Splinter behavior too
     */
    PizzaShop::extend(function($pizza){
        $pizza->implement[] = 'MyNamespace.Behaviors.MasterSplinter';
    });


### Using Traits instead of base classes

In some cases you may not wish to extend the `ExtensionBase` or `ExtendableBase` classes, due to other needs. So you can use the traits instead, although obviously the behavior methods will not be available to the parent class.

See `ExtensionTrait` and `ExtendableTrait`.
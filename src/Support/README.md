## Rain Support

The October Rain Support contains common classes relevant to supporting the other October Rain libraries. It adds the following features:

### Scaffolding

See the Scaffolding Commands section of the [Console documentation](https://github.com/octobercms/docs/blob/master/advanced-console.md).

### A true Singleton trait

A *true singleton* is a class that can ever only have a single instance, no matter what. Use it in your classes like this:

```php
class MyClass
{
    use \October\Rain\Support\Traits\Singleton;
}

$class = MyClass::instance();
```

### Global helpers

**input()**

Similar to `Input::get()` this returns an input parameter or the default value. However it supports HTML Array names. Booleans are also converted from strings.
```php
$value = input('value', 'not found');
$name = input('contact[name]');
$city = input('contact[location][city]');
```

### Event emitter

Adds event related features to any class.

**Attach to a class**

```php
class MyClass
{
    use October\Rain\Support\Traits\Emitter;
}
```

**Bind to an event**

```php
$myObject = new MyClass;
$myObject->bindEvent('cook.bacon', function(){
    echo 'Bacon is ready';
})
```

**Trigger an event**

```php
// Outputs: Bacon is ready
$myObject->fireEvent('cook.bacon');
```

**Bind to an event only once**

```php
$myObject = new MyClass;
$myObject->bindEvent('cook.soup', function(){
    echo 'Soup is ready. Want more? NO SOUP FOR YOU!';
}, true);
```

**Bind an event to other object method**

```php
$myObject->bindEvent('cook.eggs', [$anotherObject, 'methodToCookEggs']);
```

**Unbind an event**

```php
$myObject->unbindEvent('cook.bacon');
$myObject->unbindEvent(['cook.bacon', 'cook.eggs']);
```
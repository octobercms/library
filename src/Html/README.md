## Rain Html

An extension of `illuminate\html` and more.

### HTML helpers

These additional helpers are available in the `Helper` class.

**nameToArray**

Converts a HTML array string to a PHP array. Empty values are removed.

```php
// Converts to PHP array ['user', 'location', 'city']
$array = Helper::nameToArray('user[location][city]');
```

**strip**

Removes HTML from a string.
```php
// Outputs: Fatal Error! Oh noes!
echo Html::strip('<b>Fatal Error!</b> Oh noes!');
```

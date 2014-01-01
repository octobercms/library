Foundation - an extension of illuminate\foundation
=======

#### FacadeLoader

Allows "hot-swappable" facades, since `class_alias` cannot be changed dynamically. Facades loaded here can be changed on the fly.

```php
$facade = FacadeLoader::instance();
$facade->facade('Str', 'October\Rain\Support\Facades\Str');
$facade->facade('File', 'October\Rain\Support\Facades\File');
[...]

$str = new Str;
echo get_class($str); // Returns October\Rain\Support\Facades\Str

$facade->facade('Str', 'Some\Other\Facade\Str');
echo get_class($str); // Returns Some\Other\Facade\Str
```

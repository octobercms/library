# URL Router

URL route patterns follow an easy to read syntax and use in-place named parameters, so there is no need to use regular expressions in most cases.

## Creating a route

You should prepare your route like so:

```php
$router = new Router;

// New route with ID: myRouteId
$router->route('myRouteId', '/post/:id');

// New route with ID: anotherRouteId
$router->route('anotherRouteId', '/profile/:username');
```

## Route matching

Once you have prepared your route you can match it like this:

```php
if ($router->match('/post/2')) {

    // Returns: [id => 2]
    $params = $router->getParameters(); 

    // Returns: myRouteId
    $routeId = $router->matchedRoute(); 
}
```

## Reverse matching

You can also reverse match a route by it's identifier:

```php
// Returns: /post/2
$url = $router->url('myRouteId', ['id' => 2]);
```
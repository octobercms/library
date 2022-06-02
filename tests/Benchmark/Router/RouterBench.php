<?php

use October\Rain\Router\Router;

/**
 * @BeforeMethods({"init"})
 * @Revs(1000)
 * @Iterations(5)
 */
class RouterBench
{
    /**
     * @var array routes
     */
    protected $routes;

    /**
     * @var array routesCached
     */
    protected $routesCached;

    /**
     * @var array fixtures
     */
    protected $fixtures = [
        '/blog',
        '/blog/post',
        '/blog/post/:post_id',
        '/blog/post/:post_id?',
        '/blog/post/:post_id?',
        '/blog/post/:post_id?|^[a-z\\-]+$',
        '/blog/post/:post_id|^[0-9]+$',
        '/blog/post/:post_id|^[0-9]?$',
        '/blog/post/:post_id/:post_slug|^my-slug-.*',
        '/blog/post/:post_id?my-post',
        '/blog/post/:post_id?my-post|^[a-z]+$',
        '/color/:color/largecode/:largecode*/edit',
        '/color/:color/largecode/:largecode*/create',
        '/color/:color/largecode/:largecode*',
        '/color/:color/largecode/:largecode*|^[a-z]+\\/[a-z]+$',
        '/blog/:id*|^[0-9]+$',
        '/blog/:page?*|^[0-9\\/]+$',
        '/blog/:page?*|^[0-9]+$',
        '/blog/:year/:month/:slug*',
        '/blog/category/:category*/:page?*|^[0-9\\/]+$',
        '/blog/:year|^\\d{4}$/:month|^\\d{2}$/:day|^\\d{2}$/:slug',
        '/job/:type?request/:id',
        '/profile/:username',
        '/product/:category?/:id',
        '/portfolio/:year?noYear/:category?noCategory/:budget?noBudget',
        '/authors/:author_id|^[a-z\\-]+$/details',
        '/authors/:author_id?/details',
        '/authors/:author_id?/:details?',
        '/authors/:author_id|^[a-z\\-]+$/details/:record_type?|^[0-9]+$',
        '/authors/:author_id?my-author-id|^[a-z\\-]+$/:record_type?15|^[0-9]+$',
    ];

    /**
     * init
     */
    public function init()
    {
        $router = new Router;

        // Build padded routes
        $routes = [];
        foreach ($this->fixtures as $index => $rule) {
            $routes['pad1'.$index] = '/pad1/'.$rule;
            $routes['pad2'.$index] = '/pad2/'.$rule;
            $routes['pad3'.$index] = '/pad3/'.$rule;
            $routes['pad3'.$index] = '/pad4/'.$rule;
            $routes['pad3'.$index] = '/pad5/'.$rule;
        }

        // Final target at end (120 routes)
        foreach ($this->fixtures as $index => $rule) {
            $routes['rule'.$index] = $rule;
        }

        // Register with router
        foreach ($routes as $name => $rule) {
            $router->route($name, $rule);
        }

        $this->routes = $routes;
        $this->routesCached = $router->toArray();
    }

    /**
     * @Subject
     */
    public function benchRoute()
    {
        $router = new Router;

        foreach ($this->routes as $index => $rule) {
            $router->route('rule'.$index, $rule);
        }

        $router->match('authors/test/details');
    }

    /**
     * @Subject
     */
    public function benchRouteCached()
    {
        $router = new Router;

        $router->fromArray($this->routesCached);

        $router->match('authors/test/details');
    }
}

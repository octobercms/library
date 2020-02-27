<?php

use October\Rain\Router\Router;

class RouteTest extends TestCase
{
    public function testResolveUrl()
    {
        $params = [];
        $router = new Router();

        $rule = $router->reset()->route('testRuleId', 'blog/post');
        $result = $rule->resolveUrl('/blog/post/10', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id');
        $result = $rule->resolveUrl('blog/post/10', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals(10, $params['post_id']);

        $rule = $router->reset()->route('testRuleId', 'blog/post/:post_id?');
        $result = $rule->resolveUrl('blog/post/', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);

        $this->assertEquals(false, $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id?');
        $result = $rule->resolveUrl('blog/post/my-post', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals('my-post', $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id?|^[a-z\-]+$');
        $result = $rule->resolveUrl('blog/post/my-post', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals('my-post', $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id|^[0-9]+$');
        $result = $rule->resolveUrl('blog/post/10', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals(10, $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id?|^[a-z\-]+$');
        $result = $rule->resolveUrl('blog/post/10', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id|^[a-z\-]+$/details');
        $result = $rule->resolveUrl('authors/my-author/details', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('author_id', $params);
        $this->assertEquals('my-author', $params['author_id']);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id|^[a-z\-]+$/details');
        $result = $rule->resolveUrl('authors/details', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id?/details');
        $result = $rule->resolveUrl('authors/details', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id?/details');
        $result = $rule->resolveUrl('authors/test/details', $params);
        $this->assertTrue($result);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id?/:details?');
        $result = $rule->resolveUrl('authors/test/details', $params);
        $this->assertTrue($result);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id?/:details?');
        $result = $rule->resolveUrl('authors/test', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id|^[a-z\-]+$/details/:record_type?|^[0-9]+$');
        $result = $rule->resolveUrl('authors/my-author/details', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('author_id', $params);
        $this->assertEquals('my-author', $params['author_id']);
        $this->assertArrayHasKey('record_type', $params);
        $this->assertEquals(false, $params['record_type']);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id|^[a-z\-]+$/details/:record_type?|^[0-9]+$');
        $result = $rule->resolveUrl('authors/my-author/details/441', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('author_id', $params);
        $this->assertEquals('my-author', $params['author_id']);
        $this->assertArrayHasKey('record_type', $params);
        $this->assertEquals('441', $params['record_type']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id|^[0-9]?$');
        $result = $rule->resolveUrl('blog/post', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id?my-post');
        $result = $rule->resolveUrl('blog/post/', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals('my-post', $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/blog/post/:post_id?my-post|^[a-z]+$');
        $result = $rule->resolveUrl('blog/post/', $params);
        $this->assertTrue($result);
        $this->assertEquals(1, count($params));
        $this->assertArrayHasKey('post_id', $params);
        $this->assertEquals('my-post', $params['post_id']);

        $rule = $router->reset()->route('testRuleId', '/authors/:author_id?my-author-id|^[a-z\-]+$/:record_type?15|^[0-9]+$');
        $result = $rule->resolveUrl('authors', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('author_id', $params);
        $this->assertEquals('my-author-id', $params['author_id']);
        $this->assertArrayHasKey('record_type', $params);
        $this->assertEquals('15', $params['record_type']);

        $rule = $router->reset()->route('testRuleId', '/color/:color/largecode/:largecode*/edit');
        $result = $rule->resolveUrl('color/brown/largecode/code/with/slashes/edit', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('color', $params);
        $this->assertArrayHasKey('largecode', $params);
        $this->assertEquals('brown', $params['color']);
        $this->assertEquals('code/with/slashes', $params['largecode']);

        $rule = $router->reset()->route('testRuleId', '/color/:color/largecode/:largecode*/edit');
        $result = $rule->resolveUrl('color/brown/largecode/code/edit', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('color', $params);
        $this->assertArrayHasKey('largecode', $params);
        $this->assertEquals('brown', $params['color']);
        $this->assertEquals('code', $params['largecode']);

        $rule = $router->reset()->route('testRuleId', '/color/:color/largecode/:largecode*/create');
        $result = $rule->resolveUrl('color/brown/largecode/code/with/slashes/edit', $params);
        $this->assertFalse($result);

        $rule = $router->reset()->route('testRuleId', '/color/:color/largecode/:largecode*');
        $result = $rule->resolveUrl('color/brown/largecode/code/with/slashes/edit', $params);
        $this->assertTrue($result);
        $this->assertEquals(2, count($params));
        $this->assertArrayHasKey('color', $params);
        $this->assertArrayHasKey('largecode', $params);
        $this->assertEquals('brown', $params['color']);
        $this->assertEquals('code/with/slashes/edit', $params['largecode']);
    }

    public function testMatch()
    {
        $params = [];
        $router = new Router();

        // Set up some dummy rules
        $router->route('authorDetails', '/authors/:author_id?/:details?');
        $router->route('blogPost', 'blog/post');
        $router->route('jobRequest', 'job/:type?request/:id');

        $this->assertFalse($router->match('XXXXXXXXGARBAGE'));
        $this->assertFalse($router->match('/XXXXXXXXGARBAGE'));

        $this->assertFalse($router->match('/blog/post/10'));
        $this->assertFalse($router->match('authors/test/details/more'));
        $this->assertTrue($router->match('/authors'));
        $this->assertTrue($router->match('authors/test/details'));

        $this->assertFalse($router->match('job'));
        $this->assertFalse($router->match('job/test'));
        $this->assertTrue($router->match('job/test/4'));
    }

    public function testUrl()
    {
        $params = [];
        $router = new Router();

        // Set up some dummy rules
        $router->route('authorDetails', '/authors/:author_id?/:details?');
        $router->route('blogPost', 'blog/post');
        $router->route('userProfile', 'profile/:username');
        $router->route('jobRequest', 'job/:type?request/:id');
        $router->route('productPage', '/product/:category?/:id');
        $router->route('portfolioPage', '/portfolio/:year?noYear/:category?noCategory/:budget?noBudget');

        $result = $router->url('blogPost');
        $this->assertEquals('/blog/post', $result);

        $result = $router->url('authorDetails');
        $this->assertEquals('/authors', $result);

        $result = $router->url('authorDetails', ['author_id' => 20]);
        $this->assertEquals('/authors/20', $result);

        $result = $router->url('authorDetails', ['details' => 'history']);
        $this->assertEquals('/authors/default/history', $result);

        $result = $router->url('authorDetails', ['author_id' => 'default']);
        $this->assertEquals('/authors/default', $result);

        $result = $router->url('userProfile', ['username' => 'shaq']);
        $this->assertEquals('/profile/shaq', $result);

        $result = $router->url('jobRequest', ['id' => '9']);
        $this->assertEquals('/job/request/9', $result);

        $result = $router->url('jobRequest');
        $this->assertEquals('/job/request/default', $result);

        $result = $router->url('productPage', ['id' => '7']);
        $this->assertEquals('/product/default/7', $result);

        $result = $router->url('productPage', ['id' => '7', 'category' => 'helmets']);
        $this->assertEquals('/product/helmets/7', $result);

        $result = $router->url('portfolioPage');
        $this->assertEquals('/portfolio', $result);

        $result = $router->url('portfolioPage', ['year' => '2020']);
        $this->assertEquals('/portfolio/2020', $result);

        $result = $router->url('portfolioPage', ['category' => 'shoes']);
        $this->assertEquals('/portfolio/noYear/shoes', $result);

        $result = $router->url('portfolioPage', ['category' => null, 'budget' => '50000-above']);
        $this->assertEquals('/portfolio/noYear/noCategory/50000-above', $result);

        $result = $router->url('portfolioPage', ['year' => false, 'category' => null, 'budget' => 0]);
        $this->assertEquals('/portfolio/noYear/noCategory/0', $result);

        $result = $router->url('portfolioPage', ['budget' => 0]);
        $this->assertEquals('/portfolio/noYear/noCategory/0', $result);

        $result = $router->url('portfolioPage', ['year' => '2020', 'category' => 'noCategory']);
        $this->assertEquals('/portfolio/2020', $result);

        $result = $router->url('portfolioPage', ['year' => 'default', 'category' => 'noCategory', 'budget' => '200-500']);
        $this->assertEquals('/portfolio/default/noCategory/200-500', $result);
    }
}

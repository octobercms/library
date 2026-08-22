<?php

use October\Rain\Router\Router;
use October\Rain\Router\RouteCompiler;

class RouteCompilerTest extends TestCase
{
    public function testStaticRouteMatching()
    {
        $router = new Router;
        $router->route('home', '/');
        $router->route('blogPost', 'blog/post');

        $this->assertTrue($router->match('/'));
        $this->assertEquals('home', $router->matchedRoute());

        $this->assertTrue($router->match('/blog/post'));
        $this->assertEquals('blogPost', $router->matchedRoute());
        $this->assertEquals([], $router->getParameters());

        // Case-insensitive, same as sequential matching
        $this->assertTrue($router->match('/Blog/POST'));
        $this->assertEquals('blogPost', $router->matchedRoute());

        // Trailing and duplicate slashes are normalized
        $this->assertTrue($router->match('blog/post/'));
        $this->assertTrue($router->match('/blog//post'));

        $this->assertFalse($router->match('/blog'));
        $this->assertFalse($router->match('/blog/post/extra'));
    }

    public function testStaticRouteFirstRuleWins()
    {
        $router = new Router;
        $router->route('first', '/blog/post');
        $router->route('second', '/blog/post');

        $this->assertTrue($router->match('/blog/post'));
        $this->assertEquals('first', $router->matchedRoute());

        // When the first rule's condition rejects, the second rule matches,
        // same as sequential matching
        $router = new Router;
        $router->route('first', '/blog/post')->condition(function () {
            return false;
        });
        $router->route('second', '/blog/post');

        $this->assertTrue($router->match('/blog/post'));
        $this->assertEquals('second', $router->matchedRoute());
    }

    public function testDynamicRouteMatching()
    {
        $router = new Router;
        $router->route('blogPost', '/blog/post/:post_id');
        $router->route('authorDetails', '/authors/:author_id?/:details?');

        $this->assertTrue($router->match('/blog/post/10'));
        $this->assertEquals('blogPost', $router->matchedRoute());
        $this->assertEquals(['post_id' => '10'], $router->getParameters());

        // Static segments match case-insensitively
        $this->assertTrue($router->match('/BLOG/Post/10'));
        $this->assertEquals(['post_id' => '10'], $router->getParameters());

        $this->assertTrue($router->match('/authors'));
        $this->assertEquals(['author_id' => false, 'details' => false], $router->getParameters());

        $this->assertTrue($router->match('/authors/shaq'));
        $this->assertEquals(['author_id' => 'shaq', 'details' => false], $router->getParameters());

        $this->assertTrue($router->match('/authors/shaq/history'));
        $this->assertEquals(['author_id' => 'shaq', 'details' => 'history'], $router->getParameters());

        $this->assertFalse($router->match('/authors/shaq/history/more'));
    }

    public function testOptionalSegmentDefaults()
    {
        $router = new Router;
        $router->route('page', '/p/:id?42');

        $this->assertTrue($router->match('/p'));
        $this->assertEquals(['id' => '42'], $router->getParameters());

        $this->assertTrue($router->match('/p/7'));
        $this->assertEquals(['id' => '7'], $router->getParameters());
    }

    public function testAllOptionalSegmentsMatchRoot()
    {
        $router = new Router;
        $router->route('page', '/:page?');

        $this->assertTrue($router->match('/'));
        $this->assertEquals(['page' => false], $router->getParameters());

        $this->assertTrue($router->match('/about'));
        $this->assertEquals(['page' => 'about'], $router->getParameters());
    }

    public function testCustomRegexKeepsCaseSensitivity()
    {
        $router = new Router;
        $router->route('post', '/x/:id|^[a-z]+$');

        $this->assertTrue($router->match('/x/abc'));

        // The custom expression is case-sensitive, even though static
        // segments match case-insensitively
        $this->assertFalse($router->match('/x/ABC'));
    }

    public function testCustomRegexKeepsSearchSemantics()
    {
        // An unanchored expression matches a substring of the segment and the
        // whole segment becomes the parameter value
        $router = new Router;
        $router->route('post', '/x/:id|[0-9]+');

        $this->assertTrue($router->match('/x/abc123'));
        $this->assertEquals(['id' => 'abc123'], $router->getParameters());

        $this->assertFalse($router->match('/x/abcdef'));
    }

    public function testCustomRegexRejectionContinuesToNextRule()
    {
        $router = new Router;
        $router->route('postById', '/blog/:id|^[0-9]+$');
        $router->route('postBySlug', '/blog/:slug');

        $this->assertTrue($router->match('/blog/10'));
        $this->assertEquals('postById', $router->matchedRoute());
        $this->assertEquals(['id' => '10'], $router->getParameters());

        $this->assertTrue($router->match('/blog/hello-world'));
        $this->assertEquals('postBySlug', $router->matchedRoute());
        $this->assertEquals(['slug' => 'hello-world'], $router->getParameters());
    }

    public function testConditionRejectionContinuesToNextRule()
    {
        $router = new Router;
        $router->route('draft', '/blog/:slug')->condition(function () {
            return false;
        });
        $router->route('published', '/blog/:slug');

        $this->assertTrue($router->match('/blog/hello'));
        $this->assertEquals('published', $router->matchedRoute());
        $this->assertEquals(['slug' => 'hello'], $router->getParameters());
    }

    public function testAfterMatchReceivesRawUrl()
    {
        $passedUrl = null;

        $router = new Router;
        $router->route('post', '/blog/:slug')->afterMatch(function ($params, $url) use (&$passedUrl) {
            $passedUrl = $url;
            $params['extra'] = true;
            return $params;
        });

        $this->assertTrue($router->match('blog/hello'));
        $this->assertEquals('blog/hello', $passedUrl);
        $this->assertEquals(['slug' => 'hello', 'extra' => true], $router->getParameters());
    }

    public function testWildcardOutranksLessSpecificDynamicRule()
    {
        $router = new Router;
        $router->route('cmsPage', '/:page/:sub?');
        $router->route('blogWild', '/blog/:slug*');

        // The wildcard rule has more static segments so it sorts first,
        // same as sequential matching after sortRules
        $this->assertTrue($router->match('/blog/foo'));
        $this->assertEquals('blogWild', $router->matchedRoute());
        $this->assertEquals(['slug' => 'foo'], $router->getParameters());

        $this->assertTrue($router->match('/blog/foo/bar/baz'));
        $this->assertEquals('blogWild', $router->matchedRoute());
        $this->assertEquals(['slug' => 'foo/bar/baz'], $router->getParameters());

        $this->assertTrue($router->match('/about/team'));
        $this->assertEquals('cmsPage', $router->matchedRoute());
        $this->assertEquals(['page' => 'about', 'sub' => 'team'], $router->getParameters());
    }

    public function testDynamicRuleOutranksLessSpecificWildcard()
    {
        $router = new Router;
        $router->route('vehiclesWild', '/vehicles/:query?*');
        $router->route('vehiclesDynamic', '/vehicles/:make/:model/:id');

        $this->assertTrue($router->match('vehicles/audi/a3/123'));
        $this->assertEquals('vehiclesDynamic', $router->matchedRoute());

        $this->assertTrue($router->match('vehicles/audi/a3/123/456'));
        $this->assertEquals('vehiclesWild', $router->matchedRoute());
    }

    public function testBucketOrderingAcrossFirstSegments()
    {
        // Routes land in different regex buckets ('blog' and the catch-all),
        // the more specific rule must still win by sort order
        $router = new Router;
        $router->route('cmsPage', '/:page');
        $router->route('blogPost', '/blog/:slug?');

        $this->assertTrue($router->match('/blog/hello'));
        $this->assertEquals('blogPost', $router->matchedRoute());

        $this->assertTrue($router->match('/blog'));
        $this->assertEquals('blogPost', $router->matchedRoute());

        $this->assertTrue($router->match('/about'));
        $this->assertEquals('cmsPage', $router->matchedRoute());
        $this->assertEquals(['page' => 'about'], $router->getParameters());
    }

    public function testCompiledStateRoundTrip()
    {
        $router = new Router;
        $router->route('blogPost', '/blog/post/:post_id');
        $router->route('blogIndex', '/blog');
        $router->route('docsWild', '/docs/:path*');

        $data = $router->toArray();

        $this->assertArrayHasKey('rules', $data);
        $this->assertArrayHasKey('compiled', $data);
        $this->assertEquals(RouteCompiler::COMPILED_VERSION, $data['compiled']['version']);

        // Simulate a serialized cache round trip
        $data = unserialize(serialize($data));

        $restored = new Router;
        $restored->fromArray($data);

        // Compiled state restored, no recompilation needed
        $this->assertTrue($restored->isCompiled());

        $this->assertTrue($restored->match('/blog/post/10'));
        $this->assertEquals('blogPost', $restored->matchedRoute());
        $this->assertEquals(['post_id' => '10'], $restored->getParameters());

        $this->assertTrue($restored->match('/blog'));
        $this->assertEquals('blogIndex', $restored->matchedRoute());

        $this->assertTrue($restored->match('/docs/a/b/c'));
        $this->assertEquals('docsWild', $restored->matchedRoute());
        $this->assertEquals(['path' => 'a/b/c'], $restored->getParameters());

        // Rules restored from cache hydrate lazily, the public API always
        // exposes Rule objects
        $this->assertContainsOnlyInstancesOf(October\Rain\Router\Rule::class, $restored->getRouteMap());
        $this->assertInstanceOf(October\Rain\Router\Rule::class, $restored->getRoute('blogIndex'));
        $this->assertEquals('/blog/post/10', $restored->url('blogPost', ['post_id' => '10']));
    }

    public function testFromArrayLegacyFormat()
    {
        $router = new Router;
        $router->route('blogPost', '/blog/post/:post_id');
        $router->route('blogIndex', '/blog');

        // Legacy format is a plain list of rules
        $legacy = $router->toArray()['rules'];

        $restored = new Router;
        $restored->fromArray($legacy);

        $this->assertFalse($restored->isCompiled());

        $this->assertTrue($restored->match('/blog/post/10'));
        $this->assertEquals('blogPost', $restored->matchedRoute());
        $this->assertTrue($restored->isCompiled());
    }

    public function testVersionMismatchRecompiles()
    {
        $router = new Router;
        $router->route('blogPost', '/blog/post/:post_id');

        $data = $router->toArray();
        $data['compiled']['version'] = -1;

        $restored = new Router;
        $restored->fromArray($data);

        // Stale compiled data is ignored, routes recompile on first match
        $this->assertFalse($restored->isCompiled());
        $this->assertTrue($restored->match('/blog/post/10'));
        $this->assertEquals(['post_id' => '10'], $restored->getParameters());
    }

    public function testInvalidationOnRouteChange()
    {
        $router = new Router;
        $router->route('blogIndex', '/blog');

        $this->assertTrue($router->match('/blog'));
        $this->assertTrue($router->isCompiled());

        $router->route('aboutPage', '/about');
        $this->assertFalse($router->isCompiled());

        $this->assertTrue($router->match('/about'));
        $this->assertEquals('aboutPage', $router->matchedRoute());

        $router->reset();
        $this->assertFalse($router->isCompiled());
        $this->assertFalse($router->match('/blog'));
    }

    public function testSaveAndLoadCompiledRoutes()
    {
        $path = tempnam(sys_get_temp_dir(), 'october-routes');

        try {
            $router = new Router;
            $router->route('blogPost', '/blog/post/:post_id');
            $router->route('blogIndex', '/blog');

            $this->assertTrue($router->saveCompiledRoutes($path));

            $restored = Router::loadCompiledRoutes($path);

            $this->assertNotNull($restored);
            $this->assertTrue($restored->isCompiled());

            $this->assertTrue($restored->match('/blog/post/10'));
            $this->assertEquals('blogPost', $restored->matchedRoute());
            $this->assertEquals(['post_id' => '10'], $restored->getParameters());
        }
        finally {
            @unlink($path);
        }

        $this->assertNull(Router::loadCompiledRoutes('/nonexistent/path/routes.php'));
    }

    public function testHasRouteAndGetRoute()
    {
        $router = new Router;
        $rule = $router->route('blogIndex', '/blog');

        $this->assertTrue($router->hasRoute('blogIndex'));
        $this->assertFalse($router->hasRoute('missing'));

        $this->assertSame($rule, $router->getRoute('blogIndex'));
        $this->assertNull($router->getRoute('missing'));
    }

    public function testTranslatedUnicodeRoutes()
    {
        $router = new Router;
        $router->route('categoryIndex', '/kategória');
        $router->route('categoryPage', '/kategória/:slug');
        $router->route('collectionWild', '/kolekcia/:path*');

        // Static routes fold case with full unicode support
        $this->assertTrue($router->match('/KATEGÓRIA'));
        $this->assertEquals('categoryIndex', $router->matchedRoute());

        // Multibyte static segments inside dynamic routes fold too
        $this->assertTrue($router->match('/kategória/kožené-tašky'));
        $this->assertEquals('categoryPage', $router->matchedRoute());
        $this->assertEquals(['slug' => 'kožené-tašky'], $router->getParameters());

        $this->assertTrue($router->match('/KATEGÓRIA/tašky'));
        $this->assertEquals('categoryPage', $router->matchedRoute());
        $this->assertEquals(['slug' => 'tašky'], $router->getParameters());

        // Multibyte parameter values pass through wildcards unchanged
        $this->assertTrue($router->match('/kolekcia/jar/kožená-kabelka'));
        $this->assertEquals(['path' => 'jar/kožená-kabelka'], $router->getParameters());
    }

    public function testInvalidUtf8UrlFallsBackToSequential()
    {
        $router = new Router;
        $router->route('blogPost', '/blog/:slug');

        // Invalid UTF-8 fails the combined regex, sequential matching
        // still resolves the route without errors
        $this->assertTrue($router->match("/blog/\xC3\x28"));
        $this->assertEquals('blogPost', $router->matchedRoute());
        $this->assertEquals(['slug' => "\xC3\x28"], $router->getParameters());

        $this->assertFalse($router->match("/\xFF\xFE/nothing/here"));
    }

    public function testSharedCachePayloadAcrossInstances()
    {
        // Multiple server instances restore the same cached payload, each
        // router must work independently with no shared mutable state
        $source = new Router;
        $source->route('blogPost', '/blog/post/:post_id');
        $source->route('blogIndex', '/blog');
        $payload = unserialize(serialize($source->toArray()));

        $instanceA = new Router;
        $instanceA->fromArray($payload);
        $instanceB = new Router;
        $instanceB->fromArray($payload);

        $this->assertTrue($instanceA->match('/blog/post/10'));

        // Instance A registers an extra route locally, instance B must be
        // unaffected and keep serving from its compiled state
        $instanceA->route('extraPage', '/extra');
        $this->assertFalse($instanceA->isCompiled());
        $this->assertTrue($instanceB->isCompiled());

        $this->assertTrue($instanceA->match('/extra'));
        $this->assertFalse($instanceB->match('/extra'));
        $this->assertTrue($instanceB->match('/blog/post/10'));
        $this->assertEquals(['post_id' => '10'], $instanceB->getParameters());
    }

    public function testCorruptedCompiledStateRecompiles()
    {
        // A torn or corrupted cache write from another instance must never
        // break matching, invalid compiled data is ignored and rebuilt
        $source = new Router;
        $source->route('blogPost', '/blog/post/:post_id');
        $data = $source->toArray();

        foreach ([
            'not-an-array',
            [],
            ['version' => RouteCompiler::COMPILED_VERSION],
            ['version' => RouteCompiler::COMPILED_VERSION, 'staticRoutes' => 'garbage', 'dynamicRegexes' => ['' => '#broken('], 'dynamicRouteMap' => [], 'fallbackRules' => []],
        ] as $corrupted) {
            $restored = new Router;
            $restored->fromArray(['rules' => $data['rules'], 'compiled' => is_array($corrupted) ? $corrupted : []]);

            $this->assertTrue($restored->match('/blog/post/10'), 'Failed for corrupted payload: ' . json_encode($corrupted));
            $this->assertEquals('blogPost', $restored->matchedRoute());
            $this->assertEquals(['post_id' => '10'], $restored->getParameters());
        }
    }

    public function testParityWithSequentialMatching()
    {
        // The full fixture set from the benchmark suite, every URL must
        // produce the same result as Rule::resolveUrlSegments does
        $routes = [
            'home' => '/',
            'blogIndex' => '/blog',
            'blogPost' => '/blog/post/:post_id',
            'blogPostOptional' => '/blog/post/:post_id?',
            'blogPostSlug' => '/blog/post/:post_id/:post_slug|^my-slug-.*',
            'blogArchive' => '/blog/:year|^\d{4}$/:month|^\d{2}$/:day|^\d{2}$/:slug',
            'jobRequest' => '/job/:type?request/:id',
            'userProfile' => '/profile/:username',
            'productPage' => '/product/:category?/:id',
            'portfolioPage' => '/portfolio/:year?noYear/:category?noCategory/:budget?noBudget',
            'authorDetails' => '/authors/:author_id|^[a-z\-]+$/details/:record_type?|^[0-9]+$',
            'largeCode' => '/color/:color/largecode/:largecode*/edit',
            'middleOptional' => '/m/:a?/end',
            'optionalWildcard' => '/files/:path?*',
            'wildcardRegex' => '/code/:code*|^[a-z]+\/[a-z]+$',
            'defaultRegex' => '/pf/:year?2020|^[0-9]+$/:tag?',
            'unicodeCategory' => '/kategória/:slug?všetko',
            'catchAll' => '/:page?',
        ];

        $urls = [
            '/',
            '/blog',
            '/blog/post/10',
            '/blog/post',
            '/blog/post/4/my-slug-test',
            '/blog/post/4/no-slug-test',
            '/blog/2021/03/31/hello-world',
            '/blog/21/03/31/hello-world',
            '/job/test/4',
            '/job/test',
            '/profile/shaq',
            '/product/7',
            '/product/helmets/7',
            '/portfolio',
            '/portfolio/2020',
            '/authors/my-author/details',
            '/authors/my-author/details/441',
            '/authors/MY-AUTHOR/details',
            '/color/brown/largecode/code/with/slashes/edit',
            '/m/end',
            '/m/x/end',
            '/m/end/x',
            '/files',
            '/files/a/b/c',
            '/code/ab/cd',
            '/code/ab/99',
            '/code/ab/cd/ef',
            '/pf',
            '/pf/1999',
            '/pf/xx',
            '/pf/1999/sale',
            '/kategória',
            '/KATEGÓRIA/tašky',
            '/lonely',
            '/does/not/exist',
            'XXXXXXXXGARBAGE',
        ];

        // Compiled matching
        $compiled = new Router;
        foreach ($routes as $name => $pattern) {
            $compiled->route($name, $pattern);
        }

        // Sequential matching over the same sorted rules
        $sequential = new Router;
        foreach ($routes as $name => $pattern) {
            $sequential->route($name, $pattern);
        }
        $sequential->sortRules();

        foreach ($urls as $url) {
            $expectedRule = false;
            $expectedParams = [];
            $segments = October\Rain\Router\Helper::segmentizeUrl($url, false);

            foreach ($sequential->getRouteMap() as $rule) {
                $parameters = [];
                if ($rule->resolveUrlSegments($segments, $parameters)) {
                    $expectedRule = $rule->name();
                    $expectedParams = $parameters;
                    break;
                }
            }

            $matched = $compiled->match($url);

            $this->assertEquals($expectedRule !== false, $matched, "Match result differs for URL: {$url}");
            $this->assertEquals($expectedRule, $compiled->matchedRoute() ?: false, "Matched rule differs for URL: {$url}");

            if ($matched) {
                $this->assertEquals($expectedParams, $compiled->getParameters(), "Parameters differ for URL: {$url}");
            }
        }
    }
}

<?php

use October\Rain\Halcyon\MemoryRepository;
use Illuminate\Cache\ArrayStore;

class MemoryRepositoryTest extends TestCase
{
    protected $repository;

    public function setUp()
    {
        $store = new ArrayStore();
        $this->repository = new MemoryRepository($store);
    }

    public function testItemsAreRetrievedFromMemoryCacheBeforeCheckingExternalCache()
    {
        $this->repository->putInMemoryCache('foo', 'bar', 10);
        $this->assertEquals('bar', $this->repository->get('foo'));
    }

    public function testItemsCanBeRetrievedFromExternalCacheAndRetrievedFromMemoryCacheAfterwards()
    {
        $this->repository->getStore()->put('foo', 'bar', 10);
        $this->assertNull($this->repository->getFromMemoryCache('foo'));
        $this->assertEquals('bar', $this->repository->get('foo'));
        $this->assertEquals('bar', $this->repository->getFromMemoryCache('foo'));
    }

    public function testItemsArePutIntoBothCaches()
    {
        $this->repository->put('foo', 'bar', 10);
        $this->assertEquals('bar', $this->repository->getStore()->get('foo'));
        $this->assertEquals('bar', $this->repository->getFromMemoryCache('foo'));
    }

    public function testMemoryCacheCanStoreAndGetFalseyValues()
    {
        $this->repository->putInMemoryCache('foo', false, 10);
        $this->assertFalse($this->repository->get('foo'));
    }

    public function testBothCachesAreIncrementedAndDecrementedCorrectly()
    {
        $this->repository->put('foo', 1, 10);
        $this->repository->increment('foo', 10);
        $this->assertEquals(11, $this->repository->getStore()->get('foo'));
        $this->assertEquals(11, $this->repository->getFromMemoryCache('foo'));
        $this->repository->decrement('foo', 10);
        $this->assertEquals(1, $this->repository->getStore()->get('foo'));
        $this->assertEquals(1, $this->repository->getFromMemoryCache('foo'));
        $this->repository->increment('bar', 10);
        $this->assertEquals(10, $this->repository->getStore()->get('bar'));
        $this->assertEquals(10, $this->repository->getFromMemoryCache('bar'));
        $this->repository->decrement('baz', 10);
        $this->assertEquals(-10, $this->repository->getStore()->get('baz'));
        $this->assertEquals(-10, $this->repository->getFromMemoryCache('baz'));
    }

    public function testBothCachesForgetKeys()
    {
        $this->repository->put('foo', 'bar', 10);
        $this->repository->forget('foo');
        $this->assertNull($this->repository->getFromMemoryCache('foo'));
        $this->assertNull($this->repository->getStore()->get('foo'));
    }

    public function testBothCachesAreFlushedCorrectly()
    {
        $this->repository->put('foo', 'bar', 10);
        $this->repository->put('baz', 'qux', 10);
        $this->repository->put('quux', 'corge', 10);
        $this->assertEquals('bar', $this->repository->getFromMemoryCache('foo'));
        $this->assertEquals('bar', $this->repository->getStore()->get('foo'));
        $this->assertEquals('qux', $this->repository->getFromMemoryCache('baz'));
        $this->assertEquals('qux', $this->repository->getStore()->get('baz'));
        $this->assertEquals('corge', $this->repository->getFromMemoryCache('quux'));
        $this->assertEquals('corge', $this->repository->getStore()->get('quux'));
        $this->repository->flush();
        $this->assertNull($this->repository->getFromMemoryCache('foo'));
        $this->assertNull($this->repository->getStore()->get('foo'));
        $this->assertNull($this->repository->getFromMemoryCache('baz'));
        $this->assertNull($this->repository->getStore()->get('bar'));
        $this->assertNull($this->repository->getFromMemoryCache('quux'));
        $this->assertNull($this->repository->getStore()->get('quux'));
    }
}

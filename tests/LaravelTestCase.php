<?php

use Illuminate\Support\Facades\Hash;
use Illuminate\Contracts\Console\Kernel;

/**
 * Class LaravelTestCase
 *
 * Extend this class to write tests for classes that use facades.
 * If the test class overrides setUp(), make sure to call parent::setUp() in it.
 */
class LaravelTestCase extends Illuminate\Foundation\Testing\TestCase
{
    /**
     * Creates the application.
     *
     * @return Symfony\Component\HttpKernel\HttpKernelInterface
     */
    public function createApplication()
    {
        $app = require __DIR__.'/app/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        Hash::setRounds(4);

        return $app;
    }

    public function testNothing()
    {
        // Test nothing
    }
}

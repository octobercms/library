<?php

use Illuminate\Container\Attributes\Bind;
use October\Rain\Foundation\Application;
use October\Rain\Foundation\Bootstrap\LoadConfiguration;

/**
 * LoadConfigurationTest
 */
class LoadConfigurationTest extends TestCase
{
    /**
     * @var string basePath is a temporary application directory
     */
    protected $basePath;

    /**
     * setUp test
     */
    public function setUp(): void
    {
        $this->basePath = sys_get_temp_dir() . '/rain-load-config-' . uniqid();
        mkdir($this->basePath . '/config', 0777, true);
        file_put_contents($this->basePath . '/config/app.php', "<?php return ['env' => 'testing'];");
    }

    /**
     * tearDown test
     */
    public function tearDown(): void
    {
        unlink($this->basePath . '/config/app.php');
        rmdir($this->basePath . '/config');
        rmdir($this->basePath);
    }

    /**
     * testBindAttributeResolvesAfterBootstrap confirms the container can resolve bindings declared with attributes
     */
    public function testBindAttributeResolvesAfterBootstrap()
    {
        $previous = Illuminate\Container\Container::getInstance();

        try {
            $app = new Application($this->basePath);
            (new LoadConfiguration)->bootstrap($app);

            $this->assertInstanceOf(
                LoadConfigurationTestService::class,
                $app->make(LoadConfigurationTestContract::class)
            );
        }
        finally {
            Illuminate\Container\Container::setInstance($previous);
        }
    }
}

#[Bind(LoadConfigurationTestService::class)]
interface LoadConfigurationTestContract
{
}

class LoadConfigurationTestService implements LoadConfigurationTestContract
{
}

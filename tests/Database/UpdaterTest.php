<?php

use October\Rain\Database\Updater;
use October\Rain\Tests\Database\SampleFiles\SampleClass;

class UpdaterTest extends TestCase
{
    public function setUp()
    {
        $this->updater = new Updater();
    }

    public function testClassNameGetsParsedCorrectly()
    {
        $reflector = new ReflectionClass(SampleClass::class);
        $filePath = $reflector->getFileName();

        $classFullName = $this->updater->getClassFromFile($filePath);

        $expectedString = 'October\Rain\Tests\Database\SampleFiles\SampleClass';
        $this->assertEquals($expectedString, $classFullName);
    }
}

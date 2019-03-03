<?php

namespace October\Rain\Tests\Database\SampleFiles;

use PHPUnit\Framework\TestCase;

class SampleClass
{
    const SAMPLE = 'sample';

    protected $sampleClass;

    public function __construct()
    {
        if (class_exists(TestCase::class)) {
            $this->sampleClass = TestCase::class;
        }
    }
}

<?php

namespace October\Rain\Tests\fixtures\Database;

use October\Rain\Supports\Arr;

class SampleClass
{
    const SAMPLE = 'sample';

    protected $sampleClass;

    public function __construct()
    {
        if (class_exists(Arr::class)) {
            $this->sampleClass = Arr::class;
        }
    }
}

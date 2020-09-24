<?php

use October\Rain\Support\Arr;

class ArrTest extends TestCase
{
    public function testArrClass()
    {
        $array = [
            'test' => 'value',
            'test2.child1' => 'value2',
            'test2.child2.grandchild1' => 'value3',
            'test2.child3.0.name' => 'Ben',
            'test2.child3.0.surname' => 'Thomson',
            'test2.child3.1.name' => 'John',
            'test2.child3.1.surname' => 'Doe',
        ];

        $this->assertEquals([
            'test' => 'value',
            'test2' => [
                'child1' => 'value2',
                'child2' => [
                    'grandchild1' => 'value3',
                ],
                'child3' => [
                    [
                        'name' => 'Ben',
                        'surname' => 'Thomson',
                    ],
                    [
                        'name' => 'John',
                        'surname' => 'Doe'
                    ]
                ]
            ]
        ], Arr::undot($array));
    }

    public function testHelper()
    {
        $array = [
            'test' => 'value',
            'test2.child1' => 'value2',
            'test2.child2.grandchild1' => 'value3',
            'test2.child3.0.name' => 'Ben',
            'test2.child3.0.surname' => 'Thomson',
            'test2.child3.1.name' => 'John',
            'test2.child3.1.surname' => 'Doe',
        ];

        $this->assertEquals([
            'test' => 'value',
            'test2' => [
                'child1' => 'value2',
                'child2' => [
                    'grandchild1' => 'value3',
                ],
                'child3' => [
                    [
                        'name' => 'Ben',
                        'surname' => 'Thomson',
                    ],
                    [
                        'name' => 'John',
                        'surname' => 'Doe'
                    ]
                ]
            ]
        ], array_undot($array));
    }
}

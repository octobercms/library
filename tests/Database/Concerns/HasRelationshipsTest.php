<?php

use October\Rain\Database\Model;

class HasRelationshipsTest extends TestCase
{
    public function testGetRelationTypeDefinitions()
    {
        $model = new TestModelBelongsTo();
        $this->assertEquals([], $model->getRelationTypeDefinitions('belongsToMany'));
        $this->assertEquals([
            'relatedModel' => 'TestModelNoRelation',
            'anotherRelatedModel' => [
                'TestModelNoRelation',
                'order' => 'name desc',
            ],
        ], $model->getRelationTypeDefinitions('belongsTo'));
    }

    public function testDynamicGetRelationTypeDefinitions()
    {
        TestModelBelongsTo::extend(function ($model) {
            $model->belongsTo['dynamicRelatedModel'] = 'TestModelNoRelation';
        });
        $model = new TestModelBelongsTo();
        $this->assertEquals([
            'relatedModel' => 'TestModelNoRelation',
            'anotherRelatedModel' => [
                'TestModelNoRelation',
                'order' => 'name desc',
            ],
            'dynamicRelatedModel' => 'TestModelNoRelation'
        ], $model->getRelationTypeDefinitions('belongsTo'));
    }

    public function testGetRelationTypeDefinition()
    {
        $model = new TestModelBelongsTo();
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsTo', 'nonExistantRelation'));
        $this->assertEquals('TestModelNoRelation', $model->getRelationTypeDefinition('belongsTo', 'relatedModel'));
        $this->assertEquals(['TestModelNoRelation', 'order' => 'name desc'], $model->getRelationTypeDefinition('belongsTo', 'anotherRelatedModel'));
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsToMany', 'nonExistantRelation'));
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsToMany', 'relatedModel'));
    }

    public function testDynamicGetRelationTypeDefinition()
    {
        TestModelBelongsTo::extend(function ($model) {
            $model->belongsTo['dynamicRelatedModel'] = 'TestModelNoRelation';
        });
        $model = new TestModelBelongsTo();
        $this->assertEquals('TestModelNoRelation', $model->getRelationTypeDefinition('belongsTo', 'dynamicRelatedModel'));
    }
}

/*
 * Class with belongsTo relation
 */
class TestModelBelongsTo extends Model
{
    public $belongsTo = [
        'relatedModel' => 'TestModelNoRelation',
        'anotherRelatedModel' => [
            'TestModelNoRelation',
            'order' => 'name desc',
        ]
    ];
}

/*
 * Class with no belongsTo relation
 */
class TestModelNoRelation extends Model
{

}

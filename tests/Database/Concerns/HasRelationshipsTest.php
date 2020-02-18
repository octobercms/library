<?php

use October\Rain\Database\Model;

class HasRelationshipsTest extends TestCase
{
    public function testGetRelationTypeDefinitions()
    {
        $model = new TestModelBelongsTo();
        $this->assertEquals([], $model->getRelationTypeDefinitions('belongsToMany'));
        $this->assertEquals(['relatedModel' => 'TestModelNoRelation'], $model->getRelationTypeDefinitions('belongsTo'));
    }

    public function testDynamicGetRelationTypeDefinitions()
    {
        TestModelBelongsTo::extend(function ($model) {
            $model->belongsTo['secondRelatedModel'] = 'TestModelNoRelation';
        });
        $model = new TestModelBelongsTo();
        $this->assertEquals([
            'relatedModel' => 'TestModelNoRelation',
            'secondRelatedModel' => 'TestModelNoRelation'
        ], $model->getRelationTypeDefinitions('belongsTo'));
    }

    public function testGetRelationTypeDefinition()
    {
        $model = new TestModelBelongsTo();
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsTo', 'nonExistantRelation'));
        $this->assertEquals('TestModelNoRelation', $model->getRelationTypeDefinition('belongsTo', 'relatedModel'));
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsToMany', 'nonExistantRelation'));
        $this->assertEquals(null, $model->getRelationTypeDefinition('belongsToMany', 'relatedModel'));
    }

    public function testDynamicGetRelationTypeDefinition()
    {
        TestModelBelongsTo::extend(function ($model) {
            $model->belongsTo['secondRelatedModel'] = 'TestModelNoRelation';
        });
        $model = new TestModelBelongsTo();
        $this->assertEquals('TestModelNoRelation', $model->getRelationTypeDefinition('belongsTo', 'secondRelatedModel'));
    }
}

/*
 * Class with belongsTo relation
 */
class TestModelBelongsTo extends Model
{
    public $belongsTo = [
        'relatedModel' => 'TestModelNoRelation'
    ];
}

/*
 * Class with no belongsTo relation
 */
class TestModelNoRelation extends Model
{

}

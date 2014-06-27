<?php namespace October\Rain\Syntax;

trait SyntaxModelTrait
{

    public static function bootSyntaxModelTrait()
    {
        static::fetched(function($model){
            $model->applySyntaxFields();
        });
    }

    /**
     * Get data column name.
     * @return string
     */
    public function getSyntaxDataColumnName()
    {
        return defined('static::SYNTAX_DATA') ? static::SYNTAX_DATA : 'syntax_data';
    }

    /**
     * Get value of the model syntax_data column.
     * @return int
     */
    public function getSyntaxData()
    {
        return $this->getAttribute($this->getSyntaxDataColumnName());
    }

    /**
     * Get fields column name.
     * @return string
     */
    public function getSyntaxFieldsColumnName()
    {
        return defined('static::SYNTAX_FIELDS') ? static::SYNTAX_FIELDS : 'syntax_fields';
    }

    /**
     * Get value of the model syntax_fields column.
     * @return int
     */
    public function getSyntaxFields()
    {
        return $this->getAttribute($this->getSyntaxFieldsColumnName());
    }

    public function makeSyntaxFields($content)
    {
        $fields = Parser::parse($content)->toEditor();
        $newFields = [];
        foreach ($fields as $field => $params) {

            if ($params['type'] != 'fileupload')
                $newField = $this->getSyntaxDataColumnName().'['.$field.']';
            else
                $newField = $field;

            $newFields[$newField] = $params;
        }

        $this->setAttribute($this->getSyntaxFieldsColumnName(), $newFields);
        return $newFields;
    }

    public function applySyntaxFields()
    {
        $fields = $this->getSyntaxFields();
        if (!is_array($fields))
            return;

        foreach ($fields as $field => $params) {
            if (!isset($params['type'])) continue;

            if ($params['type'] == 'fileupload')
                $this->attachOne[$field] = ['System\Models\File'];
        }
    }


}
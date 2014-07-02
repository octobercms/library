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

    /**
     * Prepare the syntax fields for use in a Form builder. The array
     * name is added to each field.
     * @return array
     */
    public function getFormSyntaxFields()
    {
        $fields = $this->getSyntaxFields();
        if (!is_array($fields))
            return [];

        $newFields = [];
        foreach ($fields as $field => $params) {

            if ($params['type'] != 'fileupload')
                $newField = $this->getSyntaxDataColumnName().'['.$field.']';
            else
                $newField = $field;

            $newFields[$newField] = $params;
        }

        return $newFields;
    }

    public function makeSyntaxFields($content)
    {
        $parser = Parser::parse($content);
        $fields = $parser->toEditor() ?: [];

        $this->setAttribute($this->getSyntaxFieldsColumnName(), $fields);

        /*
         * Remove fields no longer present and add default values
         */
        $currentFields = array_intersect_key((array) $this->getSyntaxData(), $parser->getFieldValues());
        $currentFields = array_merge($currentFields, $parser->getFieldValues());
        $this->setAttribute($this->getSyntaxDataColumnName(), $currentFields);

        return $fields;
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

    public function getSyntaxParser($content)
    {
        return Parser::parse($content);
    }

    // public function syntaxRender($content)
    // {
    //     return $this->getSyntaxParser($content)->render($this->getSyntaxData());
    // }

}
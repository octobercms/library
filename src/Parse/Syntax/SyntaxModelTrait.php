<?php namespace October\Rain\Parse\Syntax;

use Request;

trait SyntaxModelTrait
{

    public static function bootSyntaxModelTrait()
    {
        static::fetched(function ($model) {
            $model->defineSyntaxRelations();
        });
    }

    /**
     * Defines any relationships (attachments) that this model will need
     * based on the field definitions.
     * @return void
     */
    public function defineSyntaxRelations()
    {
        $fields = $this->getSyntaxFields();
        if (!is_array($fields)) {
            return;
        }

        foreach ($fields as $field => $params) {
            if (!isset($params['type'])) {
                continue;
            }

            if ($params['type'] == 'fileupload') {
                $this->attachOne[$field] = 'System\Models\File';
            }
        }
    }

    /**
     * Prepare the syntax field data for saving.
     */
    public function getFormSyntaxData()
    {
        $data = $this->getSyntaxData();

        $fields = $this->getSyntaxFields();
        if (!is_array($fields)) {
            return $data;
        }

        foreach ($fields as $field => $params) {
            /*
             * File upload
             */
            if ($params['type'] == 'fileupload' && $this->hasRelation($field)) {
                if ($this->sessionKey) {
                    if ($image = $this->$field()->withDeferred($this->sessionKey)->first()) {
                        $data[$field] = $this->getThumbForImage($image, $params);
                    }
                    else {
                        unset($data[$field]);
                    }
                }
                elseif ($this->$field) {
                    $data[$field] = $this->getThumbForImage($this->$field, $params);
                }
            }
        }

        return $data;
    }

    /**
     * Helper to get the perfect sized image.
     */
    protected function getThumbForImage($image, $params = [])
    {
        $imageWidth = array_get($params, 'imageWidth');
        $imageHeight = array_get($params, 'imageHeight');
        if ($imageWidth && $imageHeight) {
            $path = $image->getThumb($imageWidth, $imageHeight, ['mode' => 'crop']);
        }
        else {
            $path = $image->getPath();
        }

        if (!starts_with($path, ['//', 'http://', 'https://'])) {
            $path = Request::getSchemeAndHttpHost() . $path;
        }

        return $path;
    }

    /**
     * Prepare the syntax fields for use in a Form builder. The array
     * name is added to each field.
     * @return array
     */
    public function getFormSyntaxFields()
    {
        $fields = $this->getSyntaxFields();
        if (!is_array($fields)) {
            return [];
        }

        $newFields = [];
        foreach ($fields as $field => $params) {
            if ($params['type'] != 'fileupload') {
                $newField = $this->getSyntaxDataColumnName().'['.$field.']';
            }
            else {
                $newField = $field;
            }

            if ($params['type'] == 'repeater') {
                $params['form']['fields'] = array_get($params, 'fields', []);
                unset($params['fields']);
            }

            $newFields[$newField] = $params;
        }

        return $newFields;
    }

    /**
     * Processes supplied content and extracts the field definitions
     * and default data. It is mixed with the current data and applied
     * to the fields and data attributes.
     * @param string $content
     * @return array
     */
    public function makeSyntaxFields($content)
    {
        $parser = Parser::parse($content);
        $fields = $parser->toEditor() ?: [];

        $this->setAttribute($this->getSyntaxFieldsColumnName(), $fields);

        /*
         * Remove fields no longer present and add default values
         */
        $currentFields = array_intersect_key((array) $this->getFormSyntaxData(), $parser->getFieldValues());
        $currentFields = $currentFields + $parser->getFieldValues();

        $this->setAttribute($this->getSyntaxDataColumnName(), $currentFields);

        return $fields;
    }

    public function getSyntaxParser($content)
    {
        return Parser::parse($content);
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
}

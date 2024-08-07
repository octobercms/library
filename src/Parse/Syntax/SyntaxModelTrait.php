<?php namespace October\Rain\Parse\Syntax;

use Request;

/**
 * SyntaxModelTrait for use in models
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
trait SyntaxModelTrait
{
    /**
     * @deprecated replace with initializeSyntaxModelTrait model.afterFetch
     */
    public static function bootSyntaxModelTrait()
    {
        static::fetched(function ($model) {
            $model->defineSyntaxRelations();
        });
    }

    /**
     * initializeSyntaxModelTrait constructor
     */
    public function initializeSyntaxModelTrait()
    {
        $this->bindEvent('model.beforeReplicate', function() {
            $this->defineSyntaxRelations();
        });
    }

    /**
     * defineSyntaxRelations defines any relationships (attachments) that this model
     * will need based on the field definitions.
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

            if ($params['type'] === 'fileupload') {
                $this->attachOne[$field] = \System\Models\File::class;
            }
        }
    }

    /**
     * getFormSyntaxData prepares the syntax field data for saving.
     */
    public function getFormSyntaxData()
    {
        $data = $this->getSyntaxData();

        $fields = $this->getSyntaxFields();
        if (!is_array($fields)) {
            return $data;
        }

        foreach ($fields as $field => $params) {
            if (!isset($params['type'])) {
                continue;
            }

            // File upload
            if ($params['type'] === 'fileupload' && $this->hasRelation($field)) {
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
     * getThumbForImage helper to get the perfect sized image.
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
     * getFormSyntaxFields prepares the syntax fields for use in a Form builder.
     * The array name is added to each field.
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
            if (!isset($params['type'])) {
                continue;
            }

            if ($params['type'] !== 'fileupload') {
                $newField = $this->getSyntaxDataColumnName().'['.$field.']';
            }
            else {
                $newField = $field;
            }

            if ($params['type'] === 'repeater') {
                $params['form']['fields'] = array_get($params, 'fields', []);
                unset($params['fields']);
            }

            $newFields[$newField] = $params;
        }

        return $newFields;
    }

    /**
     * makeSyntaxFields processes supplied content and extracts the field definitions
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

        // Remove fields no longer present and add default values
        $currentFields = array_intersect_key((array) $this->getFormSyntaxData(), $parser->getFieldValues());
        $currentFields = $currentFields + $parser->getFieldValues();

        $this->setAttribute($this->getSyntaxDataColumnName(), $currentFields);

        return $fields;
    }

    /**
     * getSyntaxParser
     */
    public function getSyntaxParser($content)
    {
        return Parser::parse($content);
    }

    /**
     * getSyntaxDataColumnName returns the data column name.
     * @return string
     */
    public function getSyntaxDataColumnName()
    {
        return defined('static::SYNTAX_DATA') ? static::SYNTAX_DATA : 'syntax_data';
    }

    /**
     * getSyntaxData returns value of the model syntax_data column.
     * @return int
     */
    public function getSyntaxData()
    {
        return $this->getAttribute($this->getSyntaxDataColumnName());
    }

    /**
     * getSyntaxFieldsColumnName returns fields column name.
     * @return string
     */
    public function getSyntaxFieldsColumnName()
    {
        return defined('static::SYNTAX_FIELDS') ? static::SYNTAX_FIELDS : 'syntax_fields';
    }

    /**
     * getSyntaxFields returns value of the model syntax_fields column.
     * @return int
     */
    public function getSyntaxFields()
    {
        return $this->getAttribute($this->getSyntaxFieldsColumnName());
    }
}

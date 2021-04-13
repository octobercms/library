<?php namespace October\Rain\Halcyon\Processors;

use October\Rain\Halcyon\Builder;

/**
 * Processor
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class Processor
{
    /**
     * Process the results of a singular "select" query.
     *
     * @param  \October\Rain\Halcyon\Builder  $query
     * @param  array  $result
     * @param  string $fileName
     * @return array
     */
    public function processSelectOne(Builder $query, $result)
    {
        if ($result === null) {
            return null;
        }

        $fileName = array_get($result, 'fileName');

        return [$fileName => $this->parseTemplateContent($query, $result, $fileName)];
    }

    /**
     * Process the results of a "select" query.
     *
     * @param  \October\Rain\Halcyon\Builder  $query
     * @param  array  $results
     * @return array
     */
    public function processSelect(Builder $query, $results)
    {
        if (!count($results)) {
            return [];
        }

        $items = [];

        foreach ($results as $result) {
            $fileName = array_get($result, 'fileName');
            $items[$fileName] = $this->parseTemplateContent($query, $result, $fileName);
        }

        return $items;
    }

    /**
     * Helper to break down template content in to a useful array.
     * @param  int     $mtime
     * @param  string  $content
     * @return array
     */
    protected function parseTemplateContent($query, $result, $fileName)
    {
        $options = [
            'isCompoundObject' => $query->getModel()->isCompoundObject()
        ];

        $content = array_get($result, 'content');

        $processed = SectionParser::parse($content, $options);

        return [
            'fileName' => $fileName,
            'content' => $content,
            'mtime' => array_get($result, 'mtime'),
            'markup' => $processed['markup'],
            'code' => $processed['code']
        ] + $processed['settings'];
    }

    /**
     * Process the data in to an insert action.
     *
     * @param  \October\Rain\Halcyon\Builder  $query
     * @param  array  $data
     * @return string
     */
    public function processInsert(Builder $query, $data)
    {
        $options = [
            'wrapCodeInPhpTags' => $query->getModel()->getWrapCode(),
            'isCompoundObject' => $query->getModel()->isCompoundObject()
        ];

        return SectionParser::render($data, $options);
    }

    /**
     * Process the data in to an update action.
     *
     * @param  \October\Rain\Halcyon\Builder  $query
     * @param  array  $data
     * @return string
     */
    public function processUpdate(Builder $query, $data)
    {
        $options = [
            'wrapCodeInPhpTags' => $query->getModel()->getWrapCode(),
            'isCompoundObject' => $query->getModel()->isCompoundObject()
        ];

        $existingData = $query->getModel()->attributesToArray();

        return SectionParser::render($data + $existingData, $options);
    }
}

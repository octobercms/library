<?php namespace October\Rain\Halcyon\Processors;

use October\Rain\Halcyon\Builder;

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
    public function processSelectOne(Builder $query, $result, $fileName)
    {
        if ($result === null) {
            return null;
        }

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
        if (count($results)) {
            foreach ($results as $fileName => &$result) {
                $result = $this->parseTemplateContent($query, $result, $fileName);
            }
        }

        return $results;
    }

    /**
     * Helper to break down template content in to a useful array.
     * @param  int     $mtime
     * @param  string  $content
     * @return array
     */
    protected function parseTemplateContent($query, $result, $fileName)
    {
        list($mtime, $content) = $result;

        $options = [
            'isCompoundObject' => $query->getModel()->isCompoundObject()
        ];

        $processed = SectionParser::parse($content, $options);

        return [
            'mtime' => $mtime,
            'fileName' => $fileName,
            'content' => $content,
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
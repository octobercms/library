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
        if ($result !== null) {
            list($mtime, $content) = $result;
            $processed = SectionProcessor::parse($content);
            $result = [
                'mtime' => $mtime,
                'fileName' => $fileName,
                'content' => $content,
                'markup' => $processed['markup'],
                'code' => $processed['code']
            ] + $processed['settings'];
        }

        return $result;
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
                $result = $this->processSelectOne($query, $result, $fileName);
            }
        }

        return $results;
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
            'wrapCodeInPhpTags' => $query->getModel()->getWrapCode()
        ];

        return SectionProcessor::render($data, $options);
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
            'wrapCodeInPhpTags' => $query->getModel()->getWrapCode()
        ];

        $existingData = $query->getModel()->attributesToArray();

        return SectionProcessor::render($data + $existingData, $options);
    }

}
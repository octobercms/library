<?php namespace October\Rain\Config;

/**
 * Configuration writer
 */
class Write
{

    public function to($contents, $arrayPaths, $values)
    {
        $patterns = [];
        $replacements = [];
        if (!is_array($arrayPaths)) $arrayPaths = [$arrayPaths];
        if (!is_array($values)) $values = [$values];

        foreach ($arrayPaths as $path) {
            $items = explode('.', $path);
            $key = array_pop($items);
            $patterns[] = $this->buildExpression($key, $items);
        }

        foreach ($values as $value) {
            $replacements[] = '$1$2'.$value.'$4';
        }

        return preg_replace($patterns, $replacements, $contents);
    }

    private function buildExpression($targetKey, $arrayItems = [])
    {
        $captures = [];

        /*
         * Opening expression for array items ($1)
         */
        $expArray = [];
        foreach ($arrayItems as $item) {
            $expArray[] = '[\'|"]'.$item.'[\'|"]\s*=>\s*(?:array\(|[\[])';
        }

        $captures[] = '(' . implode('.*', $expArray) . '.*)';

        // $2
        // targetOpen
        $captures[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*[\'|"])';

        // $3
        // targetValue
        $captures[] = '([^\'|"]*)';

        // $4
        // targetClose
        $captures[] = '([\'|"]\s*[\n|,])';

        return '/' . implode('', $captures) . '/i';
    }

}
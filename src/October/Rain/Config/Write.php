<?php namespace October\Rain\Config;

/**
 * Configuration writer
 *
 * This class lets you rewrite array values inside a basic configuration file
 * that returns a single array definition (a Laravel config file) whilst maintaining
 * the integrity of the file, leaving comments and advanced settings intact.
 *
 * The following value types are supported for writing:
 * - strings
 * - integers
 * - booleans
 *
 * Pro Regextip: Use [\s\S] instead of . for multiline support
 */
class Write
{

    public function to($contents, $newValues)
    {
        $patterns = [];
        $replacements = [];

        foreach ($newValues as $path => $value) {
            $items = explode('.', $path);
            $key = array_pop($items);

            if (is_string($value) && strpos($value, "'") === false) {
                $replaceValue = "'".$value."'";
            }
            elseif (is_string($value) && strpos($value, '"') === false) {
                $replaceValue = '"'.$value.'"';
            }
            elseif (is_bool($value)) {
                $replaceValue = ($value ? 'true' : 'false');
            }
            else {
                $replaceValue = $value;
            }

            $patterns[] = $this->buildStringExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;

            $patterns[] = $this->buildStringExpression($key, $items, '"');
            $replacements[] = '${1}${2}'.$replaceValue;

            $patterns[] = $this->buildConstantExpression($key, $items);
            $replacements[] = '${1}${2}'.$replaceValue;
        }

        return preg_replace($patterns, $replacements, $contents);
    }

    private function buildStringExpression($targetKey, $arrayItems = [], $quoteChar = "'")
    {
        $captures = [];

        // Opening expression for array items ($1)
        $captures[] = $this->buildArrayOpeningExpression($arrayItems);

        // The target key opening ($2)
        $captures[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)['.$quoteChar.']';

        // The target value to be replaced ($3)
        $captures[] = '([^'.$quoteChar.']*)';

        // The target key closure ($4)
        $captures[] = '['.$quoteChar.']';

        return '/' . implode('', $captures) . '/i';
    }

    /**
     * Common constants only (true, false, integers)
     */
    private function buildConstantExpression($targetKey, $arrayItems = [])
    {
        $captures = [];

        // Opening expression for array items ($1)
        $captures[] = $this->buildArrayOpeningExpression($arrayItems);

        // The target key opening ($2)
        $captures[] = '([\'|"]'.$targetKey.'[\'|"]\s*=>\s*)';

        // The target value to be replaced ($3)
        $captures[] = '(true|false|[\d]+)';

        return '/' . implode('', $captures) . '/i';
    }

    private function buildArrayOpeningExpression($arrayItems)
    {
        if (count($arrayItems)) {
            $itemOpen = [];
            foreach ($arrayItems as $item) {
                // The left hand array assignment
                $itemOpen[] = '[\'|"]'.$item.'[\'|"]\s*=>\s*(?:array\(|[\[])';
            }

            // Capture all opening array, halt at the first array closure
            $result = '(' . implode('[\s\S]*', $itemOpen) . '[^\)|\]]*)';
        }
        else {
            // Gotta capture something for $1
            $result = '()';
        }

        return $result;
    }

}
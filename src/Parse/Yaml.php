<?php namespace October\Rain\Parse;

use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;

/**
 * Yaml helper class
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class Yaml
{
    /**
     * Parses supplied YAML contents in to a PHP array.
     * @param string $contents YAML contents to parse.
     * @return array The YAML contents as an array.
     */
    public function parse($contents)
    {
        $yaml = new Parser;
        return $yaml->parse($contents);
    }

    /**
     * Parses YAML file contents in to a PHP array.
     * @param string $fileName File to read contents and parse.
     * @return array The YAML contents as an array.
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);
        try {
            $parsed = $this->parse($contents);
        }
        catch (\Exception $e) {
            throw new ParseException("A syntax error was detected in $fileName. " . $e->getMessage(), __LINE__, __FILE__);
        }
        return $parsed;
    }

    /**
     * Renders a PHP array to YAML format.
     * @param array $vars
     * @param array $options
     *
     * Supported options:
     * - inline: The level where you switch to inline YAML.
     * - exceptionOnInvalidType: if an exception must be thrown on invalid types.
     * - objectSupport: if object support is enabled.
     */
    public function render($vars = [], $options = [])
    {
        extract(array_merge([
            'inline' => 20,
            'exceptionOnInvalidType' => false,
            'objectSupport' => true,
        ], $options));

        $yaml = new Dumper;
        return $yaml->dump($vars, $inline, 0, $exceptionOnInvalidType, $objectSupport);
    }
}

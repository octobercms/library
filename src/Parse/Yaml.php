<?php namespace October\Rain\Parse;

use Symfony\Component\Yaml\Parser;

/**
 * Yaml helper class
 *
 * @package october\support
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
        $yaml = new Parser();
        return $yaml->parse($contents);
    }

    /**
     * Parses YAML file contents in to a PHP array.
     * @param $fileName File to read contents and parse.
     * @return array The YAML contents as an array.
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);
        return $this->parse($contents);
    }
}

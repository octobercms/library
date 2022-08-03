<?php namespace October\Rain\Parse;

use Cache;
use Symfony\Component\Yaml\Yaml as YamlComponent;
use Symfony\Component\Yaml\Dumper;
use Symfony\Component\Yaml\Parser;
use Symfony\Component\Yaml\Exception\ParseException;
use Exception;

/**
 * Yaml helper class
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class Yaml
{
    /**
     * parse supplied YAML contents in to a PHP array.
     * @param string $contents YAML contents to parse.
     * @return array The YAML contents as an array.
     */
    public function parse($contents)
    {
        $yaml = new Parser;

        return $yaml->parse($contents);
    }

    /**
     * parseFile parses YAML file contents in to a PHP array.
     * @param string $fileName File to read contents and parse.
     * @return array The YAML contents as an array.
     */
    public function parseFile($fileName)
    {
        $contents = file_get_contents($fileName);

        try {
            $parsed = $this->parse($contents);
        }
        catch (Exception $ex) {
            throw new ParseException("A syntax error was detected in $fileName. " . $ex->getMessage(), __LINE__, __FILE__);
        }

        return $parsed;
    }

    /**
     * parseFileCached parses YAML file contents in to a PHP array, with cache.
     * @param string $fileName File to read contents and parse.
     * @return array The YAML contents as an array.
     */
    public function parseFileCached($fileName)
    {
        try {
            $fileCacheKey = 'yaml::' . $fileName . '-' . filemtime($fileName);

            return Cache::remember($fileCacheKey, 43200, function () use ($fileName) {
                return $this->parseFile($fileName);
            });
        }
        catch (Exception $ex) {
            return $this->parseFile($fileName);
        }
    }

    /**
     * render a PHP array to YAML format.
     *
     * Supported options:
     * - inline: The level where you switch to inline YAML.
     * - exceptionOnInvalidType: if an exception must be thrown on invalid types.
     * - objectSupport: if object support is enabled.
     *
     * @param array $vars
     * @param array $options
     * @return string
     */
    public function render($vars = [], $options = [])
    {
        extract(array_merge([
            'inline' => 20,
            'exceptionOnInvalidType' => false,
            'objectSupport' => true,
        ], $options));

        $flags = null;

        if ($exceptionOnInvalidType) {
            $flags |= YamlComponent::DUMP_EXCEPTION_ON_INVALID_TYPE;
        }

        if ($objectSupport) {
            $flags |= YamlComponent::DUMP_OBJECT;
        }

        return (new Dumper)->dump($vars, $inline, 0, $flags);
    }
}

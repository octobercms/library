<?php namespace October\Rain\Halcyon\Processors;

use October\Rain\Parse\Ini;
use October\Rain\Support\Str;

/**
 * SectionParser parses CMS object files (pages, partials and layouts).
 * Returns the structured file information.
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class SectionParser
{
    const SECTION_SEPARATOR = '==';

    const ERROR_INI = '_PARSER_ERROR_INI';

    /**
     * render a CMS object as file content.
     * @return string
     */
    public static function render($data, $options = [])
    {
        extract(array_merge([
            'wrapCodeInPhpTags' => true,
            'isCompoundObject' => true
        ], $options));

        if (!$isCompoundObject) {
            return $data['content'] ?? '';
        }

        $iniParser = new Ini;
        $code = trim($data['code'] ?? '');
        $markup = trim($data['markup'] ?? '');

        $trim = function (&$values) use (&$trim) {
            foreach ($values as &$value) {
                if (!is_array($value)) {
                    $value = trim($value);
                } else {
                    $trim($value);
                }
            }
        };

        $settings = $data['settings'] ?? [];
        $trim($settings);

        // Build content
        //
        $content = [];

        if ($settings) {
            $content[] = $iniParser->render($settings);
        }

        if ($code) {
            if ($wrapCodeInPhpTags) {
                $code = preg_replace('/^\<\?php/', '', $code);
                $code = preg_replace('/^\<\?/', '', $code);
                $code = preg_replace('/\?>$/', '', $code);
                $code = trim($code, PHP_EOL);

                $content[] = '<?php' . PHP_EOL . $code . PHP_EOL . '?>';
            } else {
                $content[] = $code;
            }
        }

        // Strip content separator from content as a method of escape
        $content[] = implode('', self::splitContentSections($markup));

        $content = trim(implode(PHP_EOL . self::SECTION_SEPARATOR . PHP_EOL, $content));

        return $content;
    }

    /**
     * parse a CMS object file content.
     * The expected file format is following:
     * <pre>
     * INI settings section
     * ==
     * PHP code section
     * ==
     * Twig markup section
     * </pre>
     * If the content has only 2 sections they are considered as settings and Twig.
     * If there is only a single section, it is considered as Twig.
     *
     * Returns an array with the following indexes: 'settings', 'markup', 'code'.
     * The 'markup' and 'code' elements contain strings. The 'settings' element contains the
     * parsed INI file as array. If the content string doesn't contain a section, the corresponding
     * result element has null value.
     * @param string $content
     * @return array
     */
    public static function parse($content, $options = [])
    {
        extract(array_merge([
            'isCompoundObject' => true
        ], $options));

        $result = [
            'settings' => [],
            'code' => null,
            'markup' => null
        ];

        if (!$isCompoundObject || !strlen((string) $content)) {
            return $result;
        }

        $iniParser = new Ini;
        $sections = self::splitContentSections($content);
        $count = count($sections);
        foreach ($sections as &$section) {
            $section = trim($section);
        }

        if ($count >= 3) {
            $result['settings'] = @$iniParser->parse($sections[0], true)
                ?: [self::ERROR_INI => $sections[0]];

            $result['code'] = $sections[1];
            $result['code'] = preg_replace('/^\s*\<\?php/', '', $result['code']);
            $result['code'] = preg_replace('/^\s*\<\?/', '', $result['code']);
            $result['code'] = preg_replace('/\?\>\s*$/', '', $result['code']);
            $result['code'] = trim($result['code'], PHP_EOL);

            $result['markup'] = $sections[2];
        } elseif ($count === 2) {
            $result['settings'] = @$iniParser->parse($sections[0], true)
                ?: [self::ERROR_INI => $sections[0]];

            $result['markup'] = $sections[1];
        } elseif ($count === 1) {
            $result['markup'] = $sections[0];
        }

        return $result;
    }

    /**
     * parseOffset is the same as parse method, except using the line number where the
     * respective section begins is returned. Returns an array with the following indexes:
     * 'settings', 'markup', 'code'.
     * @param string $content
     * @return array
     */
    public static function parseOffset($content)
    {
        $content = Str::normalizeEol($content);
        $sections = self::splitContentSections($content);
        $count = count($sections);

        $result = [
            'settings' => null,
            'code' => null,
            'markup' => null
        ];

        if ($count >= 3) {
            $result['settings'] = self::adjustLinePosition($content);
            $result['code'] = self::calculateLinePosition($content);
            $result['markup'] = self::calculateLinePosition($content, 2);
        } elseif ($count === 2) {
            $result['settings'] = self::adjustLinePosition($content);
            $result['markup'] = self::calculateLinePosition($content);
        } elseif ($count === 1) {
            $result['markup'] = 1;
        }

        return $result;
    }

    /**
     * splitContentSections splits a block of content in to sections,
     * split by the section separator (==).
     * @param string $content
     * @return array
     */
    protected static function splitContentSections($content)
    {
        return preg_split('/^' . preg_quote(self::SECTION_SEPARATOR) . '\s*$/m', $content, -1);
    }

    /**
     * calculateLinePosition returns the line number of a found instance of CMS object
     * section separator (==). Returns the line number the instance was found.
     * @param string $content
     * @param int $instance
     * @return int
     */
    protected static function calculateLinePosition($content, $instance = 1)
    {
        $count = 0;
        $lines = explode(PHP_EOL, $content);
        foreach ($lines as $number => $line) {
            if (trim($line) === self::SECTION_SEPARATOR) {
                $count++;
            }

            if ($count === $instance) {
                return static::adjustLinePosition($content, $number);
            }
        }

        return null;
    }

    /**
     * adjustLinePosition pushes the starting line number forward since it is not always directly
     * after the separator (==). There can be an opening tag or white space in between
     * where the section really begins. The startLine is the calculated starting line
     * from calculateLinePosition(). Returns the adjusted line number.
     * @param string $content
     * @param int $startLine
     * @return int
     */
    protected static function adjustLinePosition($content, $startLine = -1)
    {
        // Account for the separator itself
        $startLine++;

        $lines = array_slice(explode(PHP_EOL, $content), $startLine);
        foreach ($lines as $line) {
            $line = trim($line);

            // Empty line
            if ($line === '') {
                $startLine++;
                continue;
            }

            // PHP line
            if ($line === '<?php' || $line === '<?') {
                $startLine++;
                continue;
            }

            // PHP namespaced line (use x;) {
            // Don't increase the line count, it will be rewritten by Cms\Classes\CodeParser
            if (preg_match_all('/(use\s+[a-z0-9_\\\\]+;\n?)/mi', $line) === 1) {
                continue;
            }

            break;
        }

        // Line 0 does not exist
        return ++$startLine;
    }
}

<?php namespace October\Rain\Parse;

use App;

/**
 * Twig helper class
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class Twig
{

    /**
     * Parses supplied Twig contents, with supplied variables.
     * @param string $contents Twig contents to parse.
     * @param string $vars Context variables.
     * @return string
     */
    public function parse($contents, $vars = [])
    {
        $twig = App::make('twig.environment');
        $template = $twig->createTemplate($contents);
        return $template->render($vars);
    }

}

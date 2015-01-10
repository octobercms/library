<?php namespace October\Rain\Support;


use Event;
use ParsedownExtra;

class Markdown extends Facade
{

    use \October\Rain\Support\Traits\Emitter;
    use \October\Rain\Support\Traits\Singleton;

    public static function parse($text)
    {
        return self::instance()->parseText($text);
    }

    /**
     * Parse text using Markdown and Markdown-Extra
     * @param  string $text Markdown text to parse
     * @return string       Resulting HTML
     */
    public function parseText($text)
    {
        $data = new MarkdownData($text);

        $this->fireEvent('beforeParse', $data, false);
        Event::fire('markdown.beforeParse', $data, false);

        $result = $data->text;

        $instance = new ParsedownExtra;
        $result = $instance->text($result);

        $data->text = $result;

        // The markdown.parse gets passed both the original
        // input and the result so far.
        $this->fireEvent('parse', [$text, $data], false);
        Event::fire('markdown.parse', [$text, $data], false);

        return $data->text;
    }
}


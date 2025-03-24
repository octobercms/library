<?php namespace October\Rain\Parse;

/**
 * ComponentParser helper class
 *
 * @package october\parse
 * @author Alexey Bobkov, Samuel Georges
 */
class ComponentParser
{
    /**
     * parse contents
     */
    public function parse($contents)
    {
        return $this->parseContentInternal($contents);
    }

    /**
     * parseContentInternal handler
     */
    protected function parseContentInternal($content)
    {
        // Optimized regular expression to match self-closing and nested custom HTML elements
        $pattern = '/<x-([a-zA-Z0-9-]+)(\s+[^>]*)?\/>|<x-([a-zA-Z0-9-]+)(\s+[^>]*)?>(.*?)<\/x-\3>/s';

        // Find all matches
        preg_match_all($pattern, $content, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            // Self-closing tag
            if (!empty($match[1])) {
                $tag = $match[1];
                $attributes = $this->parseAttributes($match[2]);
                $replacement = $this->parseContent($tag, $attributes);
                $content = str_replace($match[0], $replacement, $content);
            }
            // Nested tag
            elseif (!empty($match[3])) {
                $tag = $match[3];
                $attributes = $this->parseAttributes($match[4]);
                $attributes['slot'] = $this->parseContentInternal(trim($match[5]));
                $replacement = $this->parseContent($tag, $attributes);
                $content = str_replace($match[0], $replacement, $content);
            }
        }

        return $content;
    }

    /**
     * parseAttributes extracts attributes from the component tag
     */
    protected function parseAttributes($attributeString)
    {
        $attributes = [];
        if (preg_match_all('/(\w+)="([^"]*)"/', $attributeString, $attrMatches, PREG_SET_ORDER)) {
            foreach ($attrMatches as $attr) {
                $attributes[$attr[1]] = $attr[2];
            }
        }
        return $attributes;
    }

    /**
     * parseContent uses a global registry of components
     */
    protected function parseContent($tag, $attributes)
    {
        // ...@todo...
        return "<div data-component=\"{$tag}\"></div>";
    }
}

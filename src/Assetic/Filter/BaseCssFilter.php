<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Util\CssUtils;

/**
 * BaseCssFilter is an abstract filter for dealing with CSS.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
abstract class BaseCssFilter implements FilterInterface
{
    /**
     * @see CssUtils::filterReferences()
     */
    protected function filterReferences(string $content, callable $callback): string
    {
        return CssUtils::filterReferences($content, $callback);
    }

    /**
     * @see CssUtils::filterUrls()
     */
    protected function filterUrls(string $content, callable $callback): string
    {
        return CssUtils::filterUrls($content, $callback);
    }

    /**
     * @see CssUtils::filterImports()
     */
    protected function filterImports(string $content, callable $callback, bool $includeUrl = true): string
    {
        return CssUtils::filterImports($content, $callback, $includeUrl);
    }

    /**
     * @see CssUtils::filterIEFilters()
     */
    protected function filterIEFilters(string $content, callable $callback): string
    {
        return CssUtils::filterIEFilters($content, $callback);
    }
}

<?php namespace October\Rain\Halcyon\Theme;

interface ThemeResolverInterface
{

    /**
     * Get a theme instance.
     *
     * @param  string  $name
     * @return \October\Rain\Halcyon\Theme\ThemeInterface
     */
    public function theme($name = null);

    /**
     * Get the default theme name.
     *
     * @return string
     */
    public function getDefaultTheme();

    /**
     * Set the default theme name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultTheme($name);

}

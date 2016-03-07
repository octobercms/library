<?php namespace October\Rain\Halcyon\Theme;

class ThemeResolver implements ThemeResolverInterface
{
    /**
     * All of the registered themes.
     *
     * @var array
     */
    protected $themes = [];

    /**
     * The default theme name.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new theme resolver instance.
     *
     * @param  array  $themes
     * @return void
     */
    public function __construct(array $themes = [])
    {
        foreach ($themes as $name => $theme) {
            $this->addTheme($name, $theme);
        }
    }

    /**
     * Get a database theme instance.
     *
     * @param  string  $name
     * @return \October\Rain\Halcyon\Theme\ThemeInterface
     */
    public function theme($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultTheme();
        }

        return $this->themes[$name];
    }

    /**
     * Add a theme to the resolver.
     *
     * @param  string  $name
     * @param  \October\Rain\Halcyon\Theme\ThemeInterface  $theme
     * @return void
     */
    public function addTheme($name, ThemeInterface $theme)
    {
        $this->themes[$name] = $theme;
    }

    /**
     * Check if a theme has been registered.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasTheme($name)
    {
        return isset($this->themes[$name]);
    }

    /**
     * Get the default theme name.
     *
     * @return string
     */
    public function getDefaultTheme()
    {
        return $this->default;
    }

    /**
     * Set the default theme name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultTheme($name)
    {
        $this->default = $name;
    }
}

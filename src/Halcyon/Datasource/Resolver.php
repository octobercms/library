<?php namespace October\Rain\Halcyon\Datasource;

class Resolver implements ResolverInterface
{
    /**
     * All of the registered datasources.
     *
     * @var array
     */
    protected $datasources = [];

    /**
     * The default datasource name.
     *
     * @var string
     */
    protected $default;

    /**
     * Create a new datasource resolver instance.
     *
     * @param  array  $datasources
     * @return void
     */
    public function __construct(array $datasources = [])
    {
        foreach ($datasources as $name => $datasource) {
            $this->addDatasource($name, $datasource);
        }
    }

    /**
     * Get a database datasource instance.
     *
     * @param  string  $name
     * @return \October\Rain\Halcyon\Datasource\DatasourceInterface
     */
    public function datasource($name = null)
    {
        if (is_null($name)) {
            $name = $this->getDefaultDatasource();
        }

        return $this->datasources[$name];
    }

    /**
     * Add a datasource to the resolver.
     *
     * @param  string  $name
     * @param  \October\Rain\Halcyon\Datasource\DatasourceInterface  $datasource
     * @return void
     */
    public function addDatasource($name, DatasourceInterface $datasource)
    {
        $this->datasources[$name] = $datasource;
    }

    /**
     * Check if a datasource has been registered.
     *
     * @param  string  $name
     * @return bool
     */
    public function hasDatasource($name)
    {
        return isset($this->datasources[$name]);
    }

    /**
     * Get the default datasource name.
     *
     * @return string
     */
    public function getDefaultDatasource()
    {
        return $this->default;
    }

    /**
     * Set the default datasource name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDatasource($name)
    {
        $this->default = $name;
    }
}

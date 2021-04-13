<?php namespace October\Rain\Halcyon\Datasource;

/**
 * Resolver
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
class Resolver implements ResolverInterface
{
    /**
     * @var array datasources registrations
     */
    protected $datasources = [];

    /**
     * @var string default datasource name
     */
    protected $default;

    /**
     * __construct a new datasource resolver instance
     */
    public function __construct(array $datasources = [])
    {
        foreach ($datasources as $name => $datasource) {
            $this->addDatasource($name, $datasource);
        }
    }

    /**
     * datasource instance
     */
    public function datasource(string $name = null): DatasourceInterface
    {
        if (is_null($name)) {
            $name = $this->getDefaultDatasource();
        }

        return $this->datasources[$name];
    }

    /**
     * addDatasource to the resolver
     */
    public function addDatasource(string $name, DatasourceInterface $datasource)
    {
        $this->datasources[$name] = $datasource;
    }

    /**
     * hasDatasource checks if a datasource has been registered
     */
    public function hasDatasource(string $name): bool
    {
        return isset($this->datasources[$name]);
    }

    /**
     * getDefaultDatasource name
     */
    public function getDefaultDatasource(): ?string
    {
        return $this->default;
    }

    /**
     * setDefaultDatasource name
     */
    public function setDefaultDatasource(string $name)
    {
        $this->default = $name;
    }
}

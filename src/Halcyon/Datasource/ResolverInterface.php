<?php namespace October\Rain\Halcyon\Datasource;

interface ResolverInterface
{

    /**
     * Get a datasource instance.
     *
     * @param  string  $name
     * @return \October\Rain\Halcyon\Datasource\DatasourceInterface
     */
    public function datasource($name = null);

    /**
     * Get the default datasource name.
     *
     * @return string
     */
    public function getDefaultDatasource();

    /**
     * Set the default datasource name.
     *
     * @param  string  $name
     * @return void
     */
    public function setDefaultDatasource($name);

}

<?php namespace October\Rain\Halcyon\Datasource;

/**
 * ResolverInterface
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
interface ResolverInterface
{
    /**
     * datasource instance
     */
    public function datasource(string $name = null): DatasourceInterface;

    /**
     * getDefaultDatasource name
     */
    public function getDefaultDatasource(): ?string;

    /**
     * setDefaultDatasource name
     */
    public function setDefaultDatasource(string $name);
}

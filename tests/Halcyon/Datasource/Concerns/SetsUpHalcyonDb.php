<?php

use October\Rain\Halcyon\Datasource\DbDatasource;

trait SetsUpHalcyonDb
{
    protected DbDatasource $dbDatasource;

    protected string $dbTable = 'halcyon_templates';

    protected function setUpHalcyonDatabase(): void
    {
        $this->dbDatasource = HalcyonDbTestHarness::boot($this->dbTable);
    }

    protected function tearDownHalcyonDatabase(): void
    {
        $this->clearDbDatasourceCache();
        HalcyonDbTestHarness::teardown();
    }

    protected function clearDbDatasourceCache(): void
    {
        HalcyonDbTestHarness::clearDbDatasourceCache();
    }
}

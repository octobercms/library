<?php namespace October\Rain\Halcyon\Datasource;

/**
 * SoftDeleteDatasourceInterface for datasources that support tombstoning
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
interface SoftDeleteDatasourceInterface extends DatasourceInterface
{
    /**
     * isTemplateTrashed returns true when a soft-deleted record exists at the path
     */
    public function isTemplateTrashed(string $dirName, string $fileName, string $extension): bool;

    /**
     * selectTrashedFileNames returns file names for soft-deleted templates in a directory
     */
    public function selectTrashedFileNames(string $dirName, array $options = []): array;

    /**
     * tombstone creates a soft-deleted record for a path with no active record
     */
    public function tombstone(string $dirName, string $fileName, string $extension): bool;
}

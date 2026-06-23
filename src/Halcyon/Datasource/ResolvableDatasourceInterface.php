<?php namespace October\Rain\Halcyon\Datasource;

/**
 * ResolvableDatasourceInterface for datasources that can resolve file locations
 *
 * @package october\halcyon
 * @author Alexey Bobkov, Samuel Georges
 */
interface ResolvableDatasourceInterface extends DatasourceInterface
{
    /**
     * resolveLocalPath returns the absolute local path for a template, if available
     */
    public function resolveLocalPath(string $dirName, string $fileName, string $extension): ?string;

    /**
     * resolvePublicUrl returns a public URL for a template, if available
     */
    public function resolvePublicUrl(string $dirName, string $fileName, string $extension, array $context = []): ?string;
}

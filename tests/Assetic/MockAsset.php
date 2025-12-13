<?php

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Filter\FilterInterface;

/**
 * Class MockAsset
 *
 * This class implements the AssetInterface and can be used
 * to test Assetic filters.
 */
class MockAsset implements AssetInterface
{
    public $content;

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    public function ensureFilter(FilterInterface $filter): void
    {
    }

    public function getFilters(): array
    {
        return [];
    }

    public function clearFilters(): void
    {
    }

    public function load(?FilterInterface $additionalFilter = null): void
    {
    }

    public function dump(?FilterInterface $additionalFilter = null): string
    {
        return $this->content ?? '';
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    public function getSourceRoot(): ?string
    {
        return null;
    }

    public function getSourcePath(): ?string
    {
        return null;
    }

    public function getSourceDirectory(): ?string
    {
        return null;
    }

    public function getTargetPath(): ?string
    {
        return null;
    }

    public function setTargetPath(?string $targetPath): void
    {
    }

    public function getLastModified(): ?int
    {
        return null;
    }

    public function getVars(): array
    {
        return [];
    }

    public function setValues(array $values): void
    {
    }

    public function getValues(): array
    {
        return [];
    }
}

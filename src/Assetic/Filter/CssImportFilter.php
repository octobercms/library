<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Filter\HashableInterface;
use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Asset\HttpAsset;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Util\CssUtils;
use RuntimeException;

/**
 * CssImportFilter converts imported stylesheets to inline.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssImportFilter extends BaseCssFilter implements HashableInterface, DependencyExtractorInterface
{
    protected const MAX_IMPORT_DEPTH = 100;
    protected const MAX_IMPORTS = 10000;

    /**
     * @var FilterInterface|null importFilter
     */
    protected $importFilter;

    /**
     * @var string|null lastHash
     */
    protected $lastHash;

    /**
     * __construct
     *
     * @param FilterInterface $importFilter Filter for each imported asset
     */
    public function __construct(?FilterInterface $importFilter = null)
    {
        $this->importFilter = $importFilter ?: new CssRewriteFilter();
    }

    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset): void
    {
        $imports = 0;
        $asset->setContent($this->expandImports($asset, [], $imports));
    }

    /**
     * expandImports follows each import before rewriting its URLs for the parent.
     */
    protected function expandImports(AssetInterface $asset, array $parents, int &$imports): string
    {
        $identity = $this->getImportIdentity($asset->getSourceRoot().'/'.$asset->getSourcePath());
        if (isset($parents[$identity])) {
            throw new RuntimeException('Circular CSS import detected.');
        }
        if (count($parents) >= static::MAX_IMPORT_DEPTH) {
            throw new RuntimeException('CSS import depth limit exceeded.');
        }
        $parents[$identity] = true;

        $importFilter = $this->importFilter;
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();

        $callback = function ($matches) use ($importFilter, $sourceRoot, $sourcePath, $parents, &$imports) {
            if (!$matches['url'] || $sourceRoot === null) {
                return $matches[0];
            }

            $importRoot = $sourceRoot;

            // Absolute
            if (strpos($matches['url'], '://') !== false) {
                [$importScheme, $tmp] = explode('://', $matches['url'], 2);
                [$importHost, $importPath] = explode('/', $tmp, 2);
                $importRoot = $importScheme.'://'.$importHost;
            }
            // Protocol-relative
            elseif (strpos($matches['url'], '//') === 0) {
                [$importHost, $importPath] = explode('/', substr($matches['url'], 2), 2);
                $importRoot = '//'.$importHost;
            }
            // Root-relative
            elseif ($matches['url'][0] == '/') {
                $importPath = substr($matches['url'], 1);
            }
            // Document-relative
            elseif ($sourcePath !== null) {
                $importPath = $matches['url'];
                if ('.' != $sourceDir = dirname($sourcePath)) {
                    $importPath = $sourceDir.'/'.$importPath;
                }
            }
            else {
                return $matches[0];
            }

            $importSource = $importRoot.'/'.$importPath;
            if (strpos($importSource, '://') !== false || strpos($importSource, '//') === 0) {
                $import = new HttpAsset($importSource, [$importFilter], true);
            }
            // Ignore non-css and non-existent imports
            elseif (pathinfo($importPath, PATHINFO_EXTENSION) != 'css' || !file_exists($importSource)) {
                return $matches[0];
            }
            else {
                $import = new FileAsset($importSource, [$importFilter], $importRoot, $importPath);
            }

            if (++$imports > static::MAX_IMPORTS) {
                throw new RuntimeException('CSS import count limit exceeded.');
            }

            $import->setTargetPath($sourcePath);
            $import->load();
            $import->setContent($this->expandImports($import, $parents, $imports));

            return $import->dump();
        };

        return $this->filterImports($asset->getContent() ?? '', $callback);
    }

    /**
     * getImportIdentity normalizes aliases without changing the import's URL base.
     */
    protected function getImportIdentity(string $source): string
    {
        if (strpos($source, '://') === false && strpos($source, '//') !== 0) {
            return realpath($source) ?: $source;
        }

        $url = parse_url(strpos($source, '//') === 0 ? 'http:'.$source : $source);
        $segments = [];
        foreach (explode('/', $url['path'] ?? '') as $segment) {
            if ($segment === '..') {
                array_pop($segments);
            }
            elseif ($segment !== '.') {
                $segments[] = $segment;
            }
        }

        return strtolower($url['scheme'] ?? 'http').'://'.strtolower($url['host'] ?? '')
            .(isset($url['port']) ? ':'.$url['port'] : '').implode('/', $segments)
            .(isset($url['query']) ? '?'.$url['query'] : '');
    }

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset): void
    {
    }

    /**
     * hashAsset
     */
    public function hashAsset($asset, $localPath)
    {
        $factory = new AssetFactory($localPath);
        $children = $this->getAllChildren($factory, file_get_contents($asset), dirname($asset));

        $allFiles = [];
        foreach ($children as $child) {
            $allFiles[] = $child;
        }

        $modified = [];
        foreach ($allFiles as $file) {
            $modified[] = $file->getLastModified();
        }

        return md5(implode('|', $modified));
    }

    /**
     * setHash
     */
    public function setHash($hash)
    {
        $this->lastHash = $hash;
    }

    /**
     * hash generated for the object
     * @return string
     */
    public function hash()
    {
        return $this->lastHash ?: serialize($this);
    }

    /**
     * getAllChildren loads all children recursively
     */
    public function getAllChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        $children = [];
        $this->collectChildren($factory, $content, $loadPath, [], $children);

        return array_values($children);
    }

    /**
     * collectChildren visits dependencies once, retaining their first import order.
     */
    protected function collectChildren(AssetFactory $factory, $content, $loadPath, array $parents, array &$children): void
    {
        if (count($parents) >= static::MAX_IMPORT_DEPTH) {
            throw new RuntimeException('CSS import depth limit exceeded.');
        }

        foreach ($this->getChildren($factory, $content, $loadPath) as $child) {
            $path = $child->getSourceRoot().'/'.$child->getSourcePath();
            $identity = $this->getImportIdentity($path);
            if (isset($parents[$identity])) {
                throw new RuntimeException('Circular CSS import detected.');
            }
            if (isset($children[$identity])) {
                continue;
            }
            if (count($children) >= static::MAX_IMPORTS) {
                throw new RuntimeException('CSS import count limit exceeded.');
            }

            $children[$identity] = $child;
            $this->collectChildren(
                $factory,
                file_get_contents($path),
                dirname($path),
                $parents + [$identity => true],
                $children
            );
        }
    }

    /**
     * getChildren only returns one level of children
     */
    public function getChildren(AssetFactory $factory, $content, $loadPath = null)
    {
        if (!$loadPath) {
            return [];
        }

        $children = [];
        foreach (CssUtils::extractImports($content) as $reference) {
            // Strict check, only allow .css imports
            if (substr($reference, -4) !== '.css') {
                continue;
            }

            if (is_file($file = $loadPath.'/'.$reference)) {
                $coll = $factory->createAsset($file, [], ['root' => $loadPath]);
                foreach ($coll as $leaf) {
                    $leaf->ensureFilter($this);
                    $children[] = $leaf;
                    break;
                }
            }
        }

        return $children;
    }
}

<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Filter\HashableInterface;
use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Asset\FileAsset;
use October\Rain\Assetic\Asset\HttpAsset;
use October\Rain\Assetic\Factory\AssetFactory;
use October\Rain\Assetic\Util\CssUtils;

/**
 * CssImportFilter converts imported stylesheets to inline.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssImportFilter extends BaseCssFilter implements HashableInterface, DependencyExtractorInterface
{
    /**
     * @var mixed importFilter
     */
    protected $importFilter;

    /**
     * @var string lastHash
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
    public function filterLoad(AssetInterface $asset)
    {
        $importFilter = $this->importFilter;
        $sourceRoot = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();

        $callback = function ($matches) use ($importFilter, $sourceRoot, $sourcePath) {
            if (!$matches['url'] || $sourceRoot === null) {
                return $matches[0];
            }

            $importRoot = $sourceRoot;

            // Absolute
            if (strpos($matches['url'], '://') !== false) {
                list($importScheme, $tmp) = explode('://', $matches['url'], 2);
                list($importHost, $importPath) = explode('/', $tmp, 2);
                $importRoot = $importScheme.'://'.$importHost;
            }
            // Protocol-relative
            elseif (strpos($matches['url'], '//') === 0) {
                list($importHost, $importPath) = explode('/', substr($matches['url'], 2), 2);
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

            $import->setTargetPath($sourcePath);

            return $import->dump();
        };

        $content = $asset->getContent();
        $lastHash = md5($content);

        do {
            $content = $this->filterImports($content, $callback);
            $hash = md5($content);
        } while ($lastHash != $hash && ($lastHash = $hash));

        $asset->setContent($content);
    }

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset)
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
        $children = (new static)->getChildren($factory, $content, $loadPath);

        foreach ($children as $child) {
            $childContent = file_get_contents($child->getSourceRoot().'/'.$child->getSourcePath());
            $children = array_merge($children, (new static)->getChildren($factory, $childContent, $loadPath.'/'.dirname($child->getSourcePath())));
        }

        return $children;
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

            if (file_exists($file = $loadPath.'/'.$reference)) {
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

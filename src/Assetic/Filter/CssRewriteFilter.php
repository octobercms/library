<?php namespace October\Rain\Assetic\Filter;

use October\Rain\Assetic\Asset\AssetInterface;

/**
 * CssRewriteFilter fixes relative CSS urls.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 */
class CssRewriteFilter extends BaseCssFilter
{
    /**
     * filterLoad
     */
    public function filterLoad(AssetInterface $asset)
    {
    }

    /**
     * filterDump
     */
    public function filterDump(AssetInterface $asset)
    {
        $sourceBase = $asset->getSourceRoot();
        $sourcePath = $asset->getSourcePath();
        $targetPath = $asset->getTargetPath();

        if ($sourcePath === null || $targetPath === null || $sourcePath == $targetPath) {
            return;
        }

        // Learn how to get from the target back to the source
        if (strpos($sourceBase, '://') !== false) {
            list($scheme, $url) = explode('://', $sourceBase.'/'.$sourcePath, 2);
            list($host, $path) = explode('/', $url, 2);

            $host = $scheme.'://'.$host.'/';
            $path = false === strpos($path, '/') ? '' : dirname($path);
            $path .= '/';
        }
        else {
            // Assume source and target are on the same host
            $host = '';

            // Pop entries off the target until it fits in the source
            if (dirname($sourcePath) == '.') {
                $path = str_repeat('../', substr_count($targetPath, '/'));
            }
            elseif (($targetDir = dirname($targetPath)) == '.') {
                $path = dirname($sourcePath).'/';
            }
            else {
                $path = '';
                while (strpos($sourcePath, $targetDir) !== 0) {
                    if (($pos = strrpos($targetDir, '/')) !== false) {
                        $targetDir = substr($targetDir, 0, $pos);
                        $path .= '../';
                    }
                    else {
                        $targetDir = '';
                        $path .= '../';
                        break;
                    }
                }
                $path .= ltrim(substr(dirname($sourcePath).'/', strlen($targetDir)), '/');
            }
        }

        $content = $this->filterReferences($asset->getContent(), function ($matches) use ($host, $path) {
            // Absolute or protocol-relative or data uri
            if (
                strpos($matches['url'], '://') !== false ||
                strpos($matches['url'], '#') === 0 ||
                strpos($matches['url'], '//') === 0 ||
                strpos($matches['url'], 'data:') === 0
            ) {
                return $matches[0];
            }

            // Root relative
            if (isset($matches['url'][0]) && '/' == $matches['url'][0]) {
                return str_replace($matches['url'], $host.$matches['url'], $matches[0]);
            }

            // Document relative
            $url = $matches['url'];
            while (strpos($url, '../') === 0 && substr_count($path, '/') >= 2) {
                $path = substr($path, 0, strrpos(rtrim($path, '/'), '/') + 1);
                $url = substr($url, 3);
            }

            $parts = [];
            foreach (explode('/', $host.$path.$url) as $part) {
                if ($part === '..' && count($parts) && end($parts) !== '..') {
                    array_pop($parts);
                }
                else {
                    $parts[] = $part;
                }
            }

            return str_replace($matches['url'], implode('/', $parts), $matches[0]);
        });

        $asset->setContent($content);
    }
}

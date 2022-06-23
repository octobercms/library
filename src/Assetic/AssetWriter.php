<?php namespace October\Rain\Assetic;

use October\Rain\Assetic\Asset\AssetInterface;
use October\Rain\Assetic\Util\VarUtils;
use InvalidArgumentException;
use RuntimeException;

/**
 * AssetWriter writes assets to the filesystem.
 *
 * @author Kris Wallsmith <kris.wallsmith@gmail.com>
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
class AssetWriter
{
    /**
     * @var string dir
     */
    protected $dir;

    /**
     * @var array values
     */
    protected $values;

    /**
     * __construct
     *
     * @param string $dir
     * @param array  $values
     * @throws InvalidArgumentException
     */
    public function __construct($dir, array $values = array())
    {
        foreach ($values as $var => $vals) {
            foreach ($vals as $value) {
                if (!is_string($value)) {
                    throw new InvalidArgumentException(sprintf('All variable values must be strings, but got %s for variable "%s".', json_encode($value), $var));
                }
            }
        }

        $this->dir = $dir;
        $this->values = $values;
    }

    /**
     * writeManagerAssets
     */
    public function writeManagerAssets(AssetManager $am)
    {
        foreach ($am->getNames() as $name) {
            $this->writeAsset($am->get($name));
        }
    }

    /**
     * writeAsset
     */
    public function writeAsset(AssetInterface $asset)
    {
        foreach (VarUtils::getCombinations($asset->getVars(), $this->values) as $combination) {
            $asset->setValues($combination);

            static::write(
                $this->dir.'/'.VarUtils::resolve(
                    $asset->getTargetPath(),
                    $asset->getVars(),
                    $asset->getValues()
                ),
                $asset->dump()
            );
        }
    }

    /**
     * write
     */
    protected static function write($path, $contents)
    {
        if (!is_dir($dir = dirname($path)) && false === @mkdir($dir, 0755, true)) {
            throw new RuntimeException('Unable to create directory '.$dir);
        }

        if (false === @file_put_contents($path, $contents)) {
            throw new RuntimeException('Unable to write file '.$path);
        }
    }
}

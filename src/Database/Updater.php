<?php namespace October\Rain\Database;

use Model;
use Exception;
use ReflectionClass;

/**
 * Updater executes database migration and seed scripts based on their filename.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Updater
{
    /**
     * @var bool skippingErrors determines if exceptions should be thrown
     */
    protected static $skippingErrors = false;

    /**
     * @var array requiredPathCache paths that have already been required.
     */
    protected static $requiredPathCache = [];

    /**
     * skipErrors will continue through exceptions
     * @param  bool  $state
     */
    public static function skipErrors($state = true)
    {
        static::$skippingErrors = $state;
    }

    /**
     * setUp a migration or seed file.
     */
    public function setUp($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Model::unguard();

        if ($object instanceof Updates\Migration) {
            $this->runMethod($object, 'up');
        }
        elseif ($object instanceof Updates\Seeder) {
            $this->runMethod($object, 'run');
        }

        Model::reguard();

        return true;
    }

    /**
     * packDown a migration or seed file.
     */
    public function packDown($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Model::unguard();

        if ($object instanceof Updates\Migration) {
            $this->runMethod($object, 'down');
        }

        Model::reguard();

        return true;
    }

    /**
     * resolve a migration instance from a file.
     * @param  string  $file
     * @return object
     */
    public function resolve(string $path)
    {
        if (!is_file($path)) {
            return;
        }

        $class = $this->getClassFromFile($path);
        if (class_exists($class) && realpath($path) == (new ReflectionClass($class))->getFileName()) {
            return new $class;
        }

        $migration = static::$requiredPathCache[$path] ??= require $path;
        if (is_object($migration)) {
            return method_exists($migration, '__construct')
                ? require $path
                : clone $migration;
        }

        if (str_ends_with($class, 'class@anonymous')) {
            throw new Exception("Anonymous class in [{$path}] could not be resolved");
        }

        return new $class;
    }

    /**
     * runMethod on a migration or seed
     */
    protected function runMethod($migration, $method)
    {
        try {
            $migration->{$method}();
        }
        catch (Exception $ex) {
            if (!static::$skippingErrors) {
                throw $ex;
            }
        }
    }

    /**
     * isValidScript checks if the object is a valid update script.
     */
    protected function isValidScript($object)
    {
        if ($object instanceof Updates\Migration) {
            return true;
        }
        elseif ($object instanceof Updates\Seeder) {
            return true;
        }

        throw new Exception(sprintf(
            'Database script [%s] must inherit October\Rain\Database\Updates\Migration or October\Rain\Database\Updates\Seeder classes',
            get_class($object)
        ));
    }

    /**
     * getClassFromFile extracts the namespace and class name from a file.
     * @param string $file
     * @return string
     */
    public function getClassFromFile($file)
    {
        $fileParser = fopen($file, 'r');
        $class = $namespace = $buffer = '';
        $i = 0;

        while (!$class) {
            if (feof($fileParser)) {
                break;
            }

            $buffer .= fread($fileParser, 512);

            // Prefix and suffix string to prevent unterminated comment warning
            $tokens = token_get_all('/**/' . $buffer . '/**/');

            if (strpos($buffer, '{') === false) {
                continue;
            }

            for (; $i < count($tokens); $i++) {
                // Namespace opening
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === ';') {
                            break;
                        }

                        $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    }
                }

                // Class opening
                if ($tokens[$i][0] === T_CLASS && $tokens[$i-1][1] !== '::') {
                    // Anonymous Class
                    if ($tokens[$i-2][0] === T_NEW && $tokens[$i-4][0] === T_RETURN) {
                        $class = 'class@anonymous';
                        break;
                    }

                    $class = $tokens[$i+2][1];
                    break;
                }
            }
        }

        if (!strlen(trim($namespace)) && !strlen(trim($class))) {
            return false;
        }

        return trim($namespace) . '\\' . trim($class);
    }
}

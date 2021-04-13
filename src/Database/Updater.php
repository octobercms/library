<?php namespace October\Rain\Database;

use File;
use Eloquent;
use Exception;

/**
 * Database updater
 *
 * Executes database migration and seed scripts based on their filename.
 *
 * @package october\database
 * @author Alexey Bobkov, Samuel Georges
 */
class Updater
{
    /**
     * Sets up a migration or seed file.
     */
    public function setUp($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Eloquent::unguard();

        if ($object instanceof Updates\Migration) {
            $object->up();
        }
        elseif ($object instanceof Updates\Seeder) {
            $object->run();
        }

        Eloquent::reguard();

        return true;
    }

    /**
     * Packs down a migration or seed file.
     */
    public function packDown($file)
    {
        $object = $this->resolve($file);

        if ($object === null) {
            return false;
        }

        $this->isValidScript($object);

        Eloquent::unguard();

        if ($object instanceof Updates\Migration) {
            $object->down();
        }

        Eloquent::reguard();

        return true;
    }

    /**
     * Resolve a migration instance from a file.
     * @param  string  $file
     * @return object
     */
    public function resolve($file)
    {
        if (!File::isFile($file)) {
            return;
        }

        require_once $file;

        if ($class = $this->getClassFromFile($file)) {
            return new $class;
        }
    }

    /**
     * Checks if the object is a valid update script.
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
     * Extracts the namespace and class name from a file.
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
                /*
                 * Namespace opening
                 */
                if ($tokens[$i][0] === T_NAMESPACE) {
                    for ($j = $i + 1; $j < count($tokens); $j++) {
                        if ($tokens[$j] === ';') {
                            break;
                        }

                        $namespace .= is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j];
                    }
                }

                /*
                 * Class opening
                 */
                if ($tokens[$i][0] === T_CLASS && $tokens[$i-1][1] !== '::') {
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

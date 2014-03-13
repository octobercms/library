<?php namespace October\Rain\Filesystem;

use ZipArchive as ZipArchiveBase;

/**
 * Zip helper
 *
 * Note: Hidden files must be included explicitly.
 *
 * $zip = new ZipArchive();
 * $zip->open('my.zip', ZipArchive::OVERWRITE);
 * $zip->addDir('mydir');
 * -- or --
 * $zip->addDirContents('mydir');
 * 
 * $zip->close();
 *
 * @package october\filesystem
 * @author Alexey Bobkov, Samuel Georges
 */
class ZipArchive extends ZipArchiveBase
{

    /**
     * Wrapper for the recursiveAddDir method.
     * @param $dirName The directory to add.
     * @return boolean
     */
    public function addDir($dirName)
    {
        return $this->recursiveAddDir($dirName);
    }

    /**
     * Wrapper for the recursiveAddDir method. The difference between addDir() 
     * and addDirContents() is, that addDirContents will not add the root-directory
     * as a directory itself into the zipfile, but only the contents.
     * @param string $dirName The directory to add.
     * @return boolean
     */
    public function addDirContents($dirName)
    {
        return $this->recursiveAddDir($dirName, null, false);
    }

    /**
     * Recursively adds the passed directory and all files
     * and folders beneath it.
     * @param string $dirName The directory to add.
     * @param string $baseDir The base directory where $dirName resides.
     * @param boolean $addDir Include the basedir as directory itself to the zip file.
     * @return boolean
     */
    private function recursiveAddDir($dirName, $baseDir = null, $addDir = true)
    {
        $result = false;
        if (is_dir($dirName)) {

            $workingDir = @getcwd() ?: null;

            chdir($dirName);
            $basename = $baseDir . basename($dirName);

            if ($addDir) {
                $result = $this->addEmptyDir($basename);
                $basename = $basename . '/';
            } else {
                $basename = null;
            }

            $files = glob('*');
            foreach ($files as $file) {
                if (is_dir($file))
                    $this->recursiveAddDir($file, $basename);
                else
                    $result = $this->addFile($file, $basename . $file);
            }

            if ($workingDir)
                @chdir($workingDir);

            $result = true;
        }

        return $result;
    }
}
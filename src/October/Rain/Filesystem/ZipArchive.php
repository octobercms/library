<?php namespace October\Rain\Filesystem;

use ZipArchive as ZipArchiveBase;

/**
 * Zip helper
 *
 * $zip = new ZipArchive();
 * $zip->open('my.zip', ZipArchive::OVERWRITE);
 * $zip->addDir('mydir');
 * -- or --
 * $zip->addDirContents('mydir');
 * 
 * $zip->close();
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
     * Wrapper for the recursiveAddDir method. The difference
     * between addDir() and addDirContents() is, that
     * addDirContents will not add the root-directory as
     * a directory itself into the zipfile, but only
     * the contents.
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
     * @param boolean $addDir Include the basedir as directory itself to the zipfile
     * @return boolean
     */
    private function recursiveAddDir($dirName, $baseDir = null, $addDir = true)
    {
        $result = false;
        if (is_dir($dirName)) {

            $workingDir = getcwd();
            chdir($dirName);
            $basename = $baseDir . basename($dirName);

            if ($addDir) {
                $result = $this->addEmptyDir($basename);
                $basename = $basename . '/';
            } else {
                $basename = null;
            }

            $files = glob('*');
            foreach ($files as $f) {
                if (is_dir($f))
                    $this->recursiveAddDir($f, $basename);
                else
                    $result = $this->addFile($f, $basename . $f);
            }

            chdir($workingDir);
            $result = true;
        }

        return $result;
    }
}
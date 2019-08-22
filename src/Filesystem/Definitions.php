<?php namespace October\Rain\Filesystem;

use Config;
use Exception;

/**
 * File definitions helper.
 * Contains file extensions for common use cases.
 *
 * @package october\filesystem
 * @author Alexey Bobkov, Samuel Georges
 */
class Definitions
{

    /**
     * Entry point to request a definition set.
     * @param $type string
     * @return array
     */
    public static function get($type)
    {
        return (new self)->getDefinitions($type);
    }

    /**
     * Returns a definition set from config or from the default sets.
     * @param $type string
     * @return array
     */
    public function getDefinitions($type)
    {
        if (!method_exists($this, $type)) {
            throw new Exception(sprintf('No such definition set exists for "%s"', $type));
        }

        return (array) Config::get('cms.fileDefinitions.'.$type, $this->$type());
    }

    /**
     * Determines if a path should be ignored, sourced from the ignoreFiles 
     * and ignorePatterns definitions.
     * @todo Efficiency of this method can be improved.
     * @param string $path Specifies a path to check.
     * @return boolean Returns TRUE if the path is visible.
     */
    public static function isPathIgnored($path)
    {
        $ignoreNames = self::get('ignoreFiles');
        $ignorePatterns = self::get('ignorePatterns');

        if (in_array($path, $ignoreNames)) {
            return true;
        }

        foreach ($ignorePatterns as $pattern) {
            if (preg_match('/'.$pattern.'/', $path)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Files that can be safely ignored.
     * This list can be customized with config:
     * - cms.fileDefinitions.ignoreFiles
     */
    protected function ignoreFiles()
    {
        return [
            '.svn',
            '.git',
            '.DS_Store',
            '.AppleDouble'
        ];
    }

    /**
     * File patterns that can be safely ignored.
     * This list can be customized with config:
     * - cms.fileDefinitions.ignorePatterns
     */
    protected function ignorePatterns()
    {
        return [
            '^\..*'
        ];
    }

    /**
     * Extensions that are particularly benign.
     * This list can be customized with config:
     * - cms.fileDefinitions.defaultExtensions
     */
    protected function defaultExtensions()
    {
        return [
            'apng',
            'av1',
            'avi',
            'bmp',
            'css',
            'doc',
            'docx',
            'eot',
            'flv',
            'gif',
            'ico',
            'ics',
            'jpeg',
            'jpg',
            'js',
            'less',
            'map',
            'mkv',
            'mov',
            'mp3',
            'mp4',
            'mpeg',
            'ods',
            'odt',
            'ogg',
            'pdf',
            'png',
            'ppt',
            'pptx',
            'rar',
            'scss',
            'svg',
            'swf',
            'ttf',
            'txt',
            'wav',
            'webm',
            'webp',
            'wmv',
            'woff2',
            'woff',
            'xls',
            'xlsx',
            'xml',
            'zip'
        ];
    }

    /**
     * Extensions seen as public assets.
     * This list can be customized with config:
     * - cms.fileDefinitions.assetExtensions
     */
    protected function assetExtensions()
    {
        return [
            'apng',
            'bmp',
            'css',
            'eot',
            'gif',
            'ico',
            'jpeg',
            'jpg',
            'js',
            'json',
            'less',
            'md',
            'png',
            'sass',
            'scss',
            'svg',
            'ttf',
            'webp',
            'woff2',
            'woff',
            'xml'
        ];
    }

    /**
     * Extensions typically used as images.
     * This list can be customized with config:
     * - cms.fileDefinitions.imageExtensions
     */
    protected function imageExtensions()
    {
        return [            
            'apng',
            'bmp',
            'gif',
            'jpeg',
            'jpg',
            'png',
            'webp'
        ];
    }

    /**
     * Extensions typically used as video files.
     * This list can be customized with config:
     * - cms.fileDefinitions.videoExtensions
     */
    protected function videoExtensions()
    {
        return [
            'av1',
            'avi',
            'mkv',
            'mov',
            'mp4',
            'mpeg',
            'mpg',
            'webm'
        ];
    }

    /**
     * Extensions typically used as audio files.
     * This list can be customized with config:
     * - cms.fileDefinitions.audioExtensions
     */
    protected function audioExtensions()
    {
        return [
            'm4a',
            'mp3',
            'ogg',
            'wav',
            'wma'
        ];
    }
}

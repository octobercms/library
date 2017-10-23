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
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif',
            'svg',
            'js',
            'map',
            'ico',
            'css',
            'less',
            'scss',
            'ics',
            'odt',
            'doc',
            'docx',
            'pdf',
            'swf',
            'txt',
            'xml',
            'ods',
            'xls',
            'xlsx',
            'eot',
            'woff',
            'woff2',
            'ttf',
            'flv',
            'wmv',
            'mp3',
            'ogg',
            'wav',
            'avi',
            'mov',
            'mp4',
            'mpeg',
            'webm',
            'mkv',
            'rar',
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
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif',
            'ico',
            'css',
            'js',
            'woff',
            'woff2',
            'svg',
            'ttf',
            'eot',
            'json',
            'md',
            'less',
            'sass',
            'scss'
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
            'jpg',
            'jpeg',
            'bmp',
            'png',
            'webp',
            'gif'
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
            'mp4',
            'avi',
            'mov',
            'mpg',
            'mpeg',
            'mkv',
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
            'mp3',
            'wav',
            'wma',
            'm4a',
            'ogg'
        ];
    }
}

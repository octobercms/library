<?php namespace October\Rain\Filesystem;

use Config;
use Exception;

/**
 * Definitions contains file extensions for common use cases
 *
 * @package october\filesystem
 * @author Alexey Bobkov, Samuel Georges
 */
class Definitions
{
    /**
     * getDefinitions is an entry point to request a definition set
     */
    public static function get(string $type): array
    {
        return (new self)->getDefinitions($type);
    }

    /**
     * getDefinitions returns a definition set from config or from the default sets.
     */
    public function getDefinitions(string $type): array
    {
        $typeConfig = snake_case($type);
        $typeMethod = studly_case($type);

        if (!method_exists($this, $typeMethod)) {
            throw new Exception(sprintf('No such definition set exists for "%s"', $type));
        }

        // Support studly and snake based config
        return (array) Config::get('cms.file_definitions.'.$typeConfig,
            Config::get('cms.fileDefinitions.'.$typeMethod,
                $this->$typeMethod()
            )
        );
    }

    /**
     * isPathIgnored determines if a path should be ignored
     * @param string $path
     * @return boolean
     */
    public static function isPathIgnored($path)
    {
        $ignoreNames = self::get('ignore_files');
        $ignorePatterns = self::get('ignore_patterns');

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
     * ignoreFiles that can be safely ignored.
     * This list can be customized with config:
     * - cms.file_definitions.ignore_files
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
     * ignorePatterns that can be safely ignored.
     * This list can be customized with config:
     * - cms.file_definitions.ignore_patterns
     */
    protected function ignorePatterns()
    {
        return [
            '^\..*'
        ];
    }

    /**
     * defaultExtensions that are particularly benign.
     * This list can be customized with config:
     * - cms.file_definitions.default_extensions
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
            'ppt',
            'pptx',
            'pdf',
            'swf',
            'txt',
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
     * assetExtensions seen as public assets.
     * This list can be customized with config:
     * - cms.file_definitions.asset_extensions
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
     * imageExtensions typically used as images.
     * This list can be customized with config:
     * - cms.file_definitions.image_extensions
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
     * videoExtensions typically used as video files.
     * This list can be customized with config:
     * - cms.file_definitions.video_extensions
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
     * audioExtensions typically used as audio files.
     * This list can be customized with config:
     * - cms.file_definitions.audio_extensions
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

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
            'gif',
            'svg',
            'js',
            'map',
            'ico',
            'css',
            'less',
            'scss',
            'pdf',
            'swf',
            'txt',
            'xml',
            'xls',
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
     * Extensions that may execute as scripts. Sourced from:
     * https://en.wikipedia.org/wiki/Server-side_scripting
     *
     * This list can be customized with config:
     * - cms.fileDefinitions.blockedExtensions
     */
    protected function blockedExtensions()
    {
        return [
            'asp',
            'avfp',
            'aspx',
            'cshtml',
            'cfm',
            'go',
            'gsp',
            'hs',
            'jsp',
            'ssjs',
            'js',
            'lasso',
            'lp',
            'op',
            'lua',
            'p',
            'cgi',
            'ipl',
            'pl',
            'php',
            'php3',
            'php4',
            'phtml',
            'py',
            'rhtml',
            'rb',
            'rbw',
            'smx',
            'tcl',
            'dna',
            'tpl',
            'r',
            'w',
            'wig'
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
            'gif',
            'css',
            'js',
            'woff',
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
            'gif',
            'svg'
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

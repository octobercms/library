<?php

namespace October\Rain\Installer;

use App;
use Url;
use Lang;
use Http;
use Config;
use ApplicationException;
use Exception;

/**
 * InstallManager
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class InstallManager
{
    /**
     * @var string WANT_VERSION is the default composer version string to use.
     */
    const WANT_VERSION = '^4.0';

    /**
     * instance creates a new instance of this singleton
     */
    public static function instance(): static
    {
        return App::make('october.installer');
    }

    /**
     * requestProjectDetails about a project based on its identifier.
     * @param  string $projectId
     * @return array
     */
    public function requestProjectDetails($projectId)
    {
        return $this->requestServerData('project/detail', ['id' => $projectId]);
    }

    /**
     * getComposerUrl returns the endpoint for composer
     */
    public function getComposerUrl(bool $withProtocol = true): string
    {
        $gateway = env('APP_COMPOSER_GATEWAY', Config::get('system.composer_gateway', 'gateway.octobercms.com'));

        return $withProtocol ? 'https://'.$gateway : $gateway;
    }

    /**
     * requestServerData contacts the update server for a response.
     * @param  string $uri
     * @param  array  $postData
     * @return array
     */
    public function requestServerData($uri, $postData = [])
    {
        $result = $this->makeHttpRequest($this->createServerUrl($uri), $postData);
        $contents = $result->body();

        if ($result->status() === 404) {
            throw new ApplicationException(Lang::get('system::lang.server.response_not_found'));
        }

        if ($result->status() !== 200) {
            throw new ApplicationException(
                strlen($contents)
                ? $contents
                : Lang::get('system::lang.server.response_empty')
            );
        }

        $resultData = false;

        try {
            $resultData = @json_decode($contents, true);
        }
        catch (Exception $ex) {
            throw new ApplicationException(Lang::get('system::lang.server.response_invalid'));
        }

        if ($resultData === false || (is_string($resultData) && !strlen($resultData))) {
            throw new ApplicationException(Lang::get('system::lang.server.response_invalid'));
        }

        return $resultData;
    }

    /**
     * createServerUrl creates a complete gateway server URL from supplied URI
     * @param  string $uri
     * @return string
     */
    protected function createServerUrl($uri)
    {
        $gateway = Config::get('system.update_gateway', 'https://gateway.octobercms.com/api');
        if (substr($gateway, -1) != '/') {
            $gateway .= '/';
        }

        return $gateway . $uri;
    }

    /**
     * makeHttpRequest makes a specialized server request to a URL.
     * @param string $url
     * @param array $postData
     * @return \Illuminate\Http\Client\Response
     */
    protected function makeHttpRequest($url, $postData)
    {
        // New HTTP instance
        $http = Http::asForm();

        // Post data
        $postData['protocol_version'] = '2.0';
        $postData['client'] = 'October CMS';
        $postData['server'] = base64_encode(json_encode([
            'php' => PHP_VERSION,
            'url' => Url::to('/'),
            'since' => date('c')
        ]));

        // Gateway auth
        if ($credentials = Config::get('system.update_gateway_auth')) {
            if (is_string($credentials)) {
                $credentials = explode(':', $credentials);
            }

            list($user, $pass) = $credentials;
            $http->withBasicAuth($user, $pass);
        }

        return $http->post($url, $postData);
    }
}

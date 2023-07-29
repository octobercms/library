<?php namespace October\Rain\Foundation\Console;

use Url;
use Http;
use Config;
use Illuminate\Console\Command;
use October\Rain\Composer\Manager as ComposerManager;
use Exception;

/**
 * ProjectSetCommand the project license key.
 *
 * @package october\system
 * @author Alexey Bobkov, Samuel Georges
 */
class ProjectSetCommand extends Command
{
    /**
     * @var string signature of console command
     */
    protected $signature = 'project:set {key?}';

    /**
     * The console command description.
     */
    protected $description = 'Sets the project license key.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $projectKey = (string) $this->argument('key');

        if (!$projectKey) {
            $this->comment(__("Enter a valid License Key to proceed."));

            $projectKey = trim($this->ask(__("License Key")));
        }

        try {
            // Validate input with gateway
            $result = $this->requestServerData('project/detail', ['id' => $projectKey]);

            // Check project status
            $isActive = $result['is_active'] ?? false;
            if (!$isActive) {
                $this->output->error(__("License is unpaid or has expired. Please visit octobercms.com to obtain a license."));
                return;
            }

            // Store project details
            $this->storeProjectDetails($result);

            // Add gateway as a composer repo
            ComposerManager::instance()->addOctoberRepository($this->getComposerUrl());

            $this->output->success(__("Thanks for being a customer of October CMS!"));
        }
        catch (Exception $e) {
            $this->output->error($e->getMessage());
            return 1;
        }
    }

    /**
     * storeProjectDetails
     */
    protected function storeProjectDetails($result)
    {
        // Save project locally
        if (class_exists(\System\Models\Parameter::class)) {
            \System\Models\Parameter::set([
                'system::project.id' => $result['id'],
                'system::project.key' => $result['project_id'],
                'system::project.name' => $result['name'],
                'system::project.owner' => $result['owner'],
                'system::project.is_active' => $result['is_active']
            ]);
        }
        else {
            if (!is_dir($cmsStorePath = storage_path('cms'))) {
                mkdir($cmsStorePath);
            }

            $this->injectJsonToFile(storage_path('cms/project.json'), [
                'project' => $result['project_id']
            ]);
        }

        // Save authentication token
        ComposerManager::instance()->addAuthCredentials(
            $this->getComposerUrl(false),
            $result['email'],
            $result['project_id']
        );
    }

    /**
     * requestServerData contacts the update server for a response.
     * @param  string $uri      Gateway API URI
     * @param  array  $postData Extra post data
     * @return array
     */
    public function requestServerData(string $uri, array $postData = [])
    {
        $result = $this->makeHttpRequest($this->createServerUrl($uri), $postData);

        $contents = $result->body();

        if ($result->status() === 404) {
            throw new Exception(__('Response Not Found'));
        }

        if ($result->status() !== 200) {
            throw new Exception(
                strlen($contents)
                ? $contents
                : __("Response Empty")
            );
        }

        $resultData = false;

        try {
            $resultData = @json_decode($contents, true);
        }
        catch (Exception $ex) {
            throw new Exception(__("Response Invalid"));
        }

        if ($resultData === false || (is_string($resultData) && !strlen($resultData))) {
            throw new Exception(__("Response Bad Format"));
        }

        return $resultData;
    }

    /**
     * createServerUrl creates a complete gateway server URL from supplied URI
     * @param  string $uri URI
     * @return string      URL
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

    /**
     * getComposerUrl returns the endpoint for composer
     */
    protected function getComposerUrl(bool $withProtocol = true): string
    {
        $gateway = Config::get('system.composer_gateway', 'gateway.octobercms.com');

        return $withProtocol ? 'https://'.$gateway : $gateway;
    }

    /**
     * injectJsonToFile merges a JSON array in to an existing JSON file.
     * Merging is useful for preserving array values.
     */
    protected function injectJsonToFile(string $filename, array $jsonArr, bool $merge = false): void
    {
        $contentsArr = file_exists($filename)
            ? json_decode(file_get_contents($filename), true)
            : [];

        $newArr = $merge
            ? array_merge_recursive($contentsArr, $jsonArr)
            : $this->mergeRecursive($contentsArr, $jsonArr);

        $content = json_encode($newArr, JSON_UNESCAPED_SLASHES|JSON_PRETTY_PRINT);

        file_put_contents($filename, $content);
    }

    /**
     * mergeRecursive substitutes the native PHP array_merge_recursive to be
     * more config friendly. Scalar values are replaced instead of being
     * merged in to their own new array.
     */
    protected function mergeRecursive(array $array1, $array2)
    {
        if ($array2 && is_array($array2)) {
            foreach ($array2 as $key => $val2) {
                if (
                    is_array($val2) &&
                    (($val1 = isset($array1[$key]) ? $array1[$key] : null) !== null) &&
                    is_array($val1)
                ) {
                    $array1[$key] = $this->mergeRecursive($val1, $val2);
                }
                else {
                    $array1[$key] = $val2;
                }
            }
        }

        return $array1;
    }
}

<?php

namespace October\Rain\Installer;

use Exception;

/**
 * Gateway Client
 *
 * A standalone PHP client for the October CMS Gateway V1 API.
 * No external dependencies required - works with PHP 7.4+
 *
 * Usage:
 *   // Account-level operations (HMAC auth)
 *   $client = (new GatewayClient)->setCredentials('your-api-key', 'your-api-secret');
 *   $projects = $client->listProjects();
 *
 *   // Project-level operations (uses license key or auth.json hash)
 *   $client = (new GatewayClient)->setProjectHash('your-license-key-or-hash');
 *   $updates = $client->checkForUpdates(['plugins' => [...], 'themes' => [...]]);
 *
 *
 */
class GatewayClient
{
    /**
     * @var string API base URL
     */
    const API_BASE_URL = 'https://api.octobercms.com';

    /**
     * @var string API version prefix
     */
    const API_VERSION = 'v1';

    /**
     * @var string|null apiKey for HMAC authentication
     */
    protected $apiKey;

    /**
     * @var string|null apiSecret for HMAC authentication
     */
    protected $apiSecret;

    /**
     * @var string|null projectHash for project-level authentication (license key or auth.json hash)
     */
    protected $projectHash;

    /**
     * @var int timeout in seconds
     */
    protected $timeout = 30;

    /**
     * @var array lastResponseHeaders
     */
    protected $lastResponseHeaders = [];

    /**
     * @var int lastStatusCode
     */
    protected $lastStatusCode = 0;

    /**
     * @var string|null lastErrorCode from API response
     */
    protected $lastErrorCode;

    // =========================================================================
    // CONFIGURATION
    // =========================================================================

    /**
     * setCredentials for account-level authentication
     */
    public function setCredentials(string $apiKey, string $apiSecret): self
    {
        $this->apiKey = $apiKey;
        $this->apiSecret = $apiSecret;
        return $this;
    }

    /**
     * setProjectHash for project-level authentication
     *
     * @param string $projectHash License key or auth.json hash (bind code)
     */
    public function setProjectHash(string $projectHash): self
    {
        $this->projectHash = $projectHash;
        return $this;
    }

    /**
     * setTimeout for requests
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    // =========================================================================
    // ACCOUNT-LEVEL ENDPOINTS (HMAC Auth Required)
    // =========================================================================

    /**
     * createProject creates a new project
     *
     * @param string $name Project name
     * @param array $options Optional: description
     * @return array Project data including project_id and license_key
     * @throws Exception
     */
    public function createProject(string $name, array $options = []): array
    {
        return $this->requestWithHmac('projects/create', array_merge(
            ['name' => $name],
            $options
        ));
    }

    /**
     * updateProject updates an existing project
     *
     * @param int $projectId Project ID
     * @param array $options Optional: name, description
     * @return array Updated project data
     * @throws Exception
     */
    public function updateProject(int $projectId, array $options = []): array
    {
        return $this->requestWithHmac('projects/update', array_merge(
            ['project_id' => $projectId],
            $options
        ));
    }

    /**
     * deleteProject deletes a project
     *
     * @param int $projectId Project ID
     * @return array Confirmation
     * @throws Exception
     */
    public function deleteProject(int $projectId): array
    {
        return $this->requestWithHmac('projects/delete', [
            'project_id' => $projectId,
        ]);
    }

    /**
     * listProjects returns all projects for the account
     *
     * @param int $limit Results per page (1-100)
     * @param string|null $cursor Pagination cursor
     * @return array Projects list with next_cursor
     * @throws Exception
     */
    public function listProjects(int $limit = 50, ?string $cursor = null): array
    {
        $params = ['limit' => $limit];
        if ($cursor !== null) {
            $params['cursor'] = $cursor;
        }
        return $this->requestWithHmac('projects/list', $params);
    }

    /**
     * getProject returns detailed information about a project
     *
     * @param int $projectId Project ID
     * @return array Project details
     * @throws Exception
     */
    public function getProject(int $projectId): array
    {
        return $this->requestWithHmac('projects/get', [
            'project_id' => $projectId,
        ]);
    }

    /**
     * lookupByDomain looks up a project by domain name
     *
     * @param string $domainName Domain to look up
     * @return array Project ID
     * @throws Exception
     */
    public function lookupByDomain(string $domainName): array
    {
        return $this->requestWithHmac('licenses/domain', [
            'domain_name' => $domainName,
        ]);
    }

    /**
     * rotateLicense regenerates a license key
     *
     * @param int $projectId Project ID
     * @return array New license_key and license_hash
     * @throws Exception
     */
    public function rotateLicense(int $projectId): array
    {
        return $this->requestWithHmac('licenses/rotate', [
            'project_id' => $projectId,
        ]);
    }

    /**
     * attachPackage attaches a package to a project
     *
     * @param int $projectId Project ID
     * @param string $packageCode Package code (e.g., 'Author.PluginName')
     * @param string $type Package type ('plugin' or 'theme')
     * @return array Confirmation with attached, package, type
     * @throws Exception
     */
    public function attachPackage(int $projectId, string $packageCode, string $type): array
    {
        return $this->requestWithHmac('projects/packages/attach', [
            'project_id' => $projectId,
            'package' => $packageCode,
            'type' => $type,
        ]);
    }

    /**
     * detachPackage detaches a package from a project
     *
     * @param int $projectId Project ID
     * @param string $packageCode Package code (e.g., 'Author.PluginName')
     * @param string $type Package type ('plugin' or 'theme')
     * @return array Confirmation with detached, package, type
     * @throws Exception
     */
    public function detachPackage(int $projectId, string $packageCode, string $type): array
    {
        return $this->requestWithHmac('projects/packages/detach', [
            'project_id' => $projectId,
            'package' => $packageCode,
            'type' => $type,
        ]);
    }

    // =========================================================================
    // PROJECT-LEVEL ENDPOINTS
    // =========================================================================

    /**
     * getProjectDetail returns project details (project-level auth)
     *
     * @param string|null $projectHash Optional project hash override
     * @return array Project details
     * @throws Exception
     */
    public function getProjectDetail(?string $projectHash = null): array
    {
        return $this->requestWithProject('project/detail', [
            'id' => $projectHash ?? $this->projectHash,
        ]);
    }

    /**
     * checkForUpdates checks for available updates (project-level auth)
     *
     * @param array $options plugins (assoc array code=>version), themes, version, build
     * @return array Available updates
     * @throws Exception
     */
    public function checkForUpdates(array $options = []): array
    {
        $params = [];

        if (isset($options['plugins'])) {
            $params['plugins'] = base64_encode(json_encode($options['plugins']));
        }
        if (isset($options['themes'])) {
            $params['themes'] = base64_encode(json_encode($options['themes']));
        }
        if (isset($options['version'])) {
            $params['version'] = $options['version'];
        }
        if (isset($options['build'])) {
            $params['build'] = $options['build'];
        }

        return $this->requestWithProject('project/check', $params);
    }

    /**
     * getPackages returns multiple package details
     *
     * @param array $codes Package codes
     * @param string $type Package type (plugin or theme)
     * @return array Package information
     * @throws Exception
     */
    public function getPackages(array $codes, string $type = 'plugin'): array
    {
        return $this->request('package/details', [
            'names' => $codes,
            'type' => $type,
        ]);
    }

    /**
     * getPackage returns single package details
     *
     * @param string $code Package code
     * @param string $type Package type
     * @return array Package information with requirements
     * @throws Exception
     */
    public function getPackage(string $code, string $type = 'plugin'): array
    {
        return $this->requestWithProject('package/detail', [
            'name' => $code,
            'type' => $type,
        ]);
    }

    /**
     * getPackageContent returns package documentation content
     *
     * @param string $code Package code
     * @param string $type Package type
     * @return array Package info with HTML content
     * @throws Exception
     */
    public function getPackageContent(string $code, string $type = 'plugin'): array
    {
        return $this->requestWithProject('package/content', [
            'name' => $code,
            'type' => $type,
        ]);
    }

    /**
     * browsePackages returns a paginated list of packages
     *
     * @param int $page Page number
     * @param string $type Package type
     * @param int|null $version Compatibility version filter
     * @return array Paginated package list
     * @throws Exception
     */
    public function browsePackages(int $page = 1, string $type = 'plugin', ?int $version = null): array
    {
        $params = [
            'page' => $page,
            'type' => $type,
        ];
        if ($version !== null) {
            $params['version'] = $version;
        }
        return $this->request('package/browse', $params);
    }

    /**
     * searchPackages searches for packages
     *
     * @param string $query Search query
     * @param string|null $type Package type filter
     * @return array Matching packages
     * @throws Exception
     */
    public function searchPackages(string $query, ?string $type = null): array
    {
        $params = ['query' => $query];
        if ($type !== null) {
            $params['type'] = $type;
        }
        return $this->request('package/search', $params);
    }

    /**
     * getInstallDetail returns installation details
     *
     * @return array Core and package hashes
     * @throws Exception
     */
    public function getInstallDetail(): array
    {
        return $this->requestWithProject('install/detail', []);
    }

    // =========================================================================
    // HTTP CLIENT
    // =========================================================================

    /**
     * requestWithHmac makes a request with HMAC authentication
     */
    protected function requestWithHmac(string $endpoint, array $params): array
    {
        if (!$this->apiKey || !$this->apiSecret) {
            throw new Exception('API credentials required for this operation');
        }

        // Add nonce
        $params['nonce'] = (string) round(microtime(true) * 1000);

        // Build query string for signing
        $queryString = http_build_query($params, '', '&');

        // Compute signature
        $signature = base64_encode(
            hash_hmac('sha512', $queryString, base64_decode($this->apiSecret), true)
        );

        $headers = [
            'Rest-Key: ' . $this->apiKey,
            'Rest-Sign: ' . $signature,
        ];

        return $this->request($endpoint, $params, 'POST', $headers);
    }

    /**
     * requestWithProject makes a request with project-level authentication
     */
    protected function requestWithProject(string $endpoint, array $params): array
    {
        $headers = [];

        if ($this->projectHash) {
            $headers[] = 'php-auth-pw: ' . $this->projectHash;
            $params['project'] = $this->projectHash;
        }

        return $this->request($endpoint, $params, 'POST', $headers);
    }

    /**
     * request makes an HTTP request
     *
     * @param string $endpoint API endpoint
     * @param array $params Request parameters
     * @param string $method HTTP method
     * @param array $headers Additional headers
     * @return array Decoded response
     * @throws Exception
     */
    protected function request(string $endpoint, array $params = [], string $method = 'POST', array $headers = []): array
    {
        $url = self::API_BASE_URL . '/' . self::API_VERSION . '/' . ltrim($endpoint, '/');

        // Default headers
        $headers = array_merge([
            'Content-Type: application/x-www-form-urlencoded',
            'Accept: application/json',
        ], $headers);

        // Initialize cURL
        $ch = curl_init();

        if ($method === 'GET') {
            if (!empty($params)) {
                $url .= '?' . http_build_query($params);
            }
        }
        else {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params, '', '&'));
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_HEADER => true,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            throw new Exception('cURL error: ' . $error);
        }

        $this->lastStatusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);

        // Parse headers and body
        $headerString = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        $this->lastResponseHeaders = $this->parseHeaders($headerString);
        $this->lastErrorCode = null;

        // Decode JSON response
        $data = json_decode($body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid JSON response: ' . $body);
        }

        // Check for error responses
        if ($this->lastStatusCode >= 400) {
            $this->lastErrorCode = $data['error'] ?? 'unknown_error';
            $message = $data['message'] ?? 'An error occurred';
            throw new Exception($message, $this->lastStatusCode);
        }

        return $data;
    }

    /**
     * parseHeaders extracts headers from response
     */
    protected function parseHeaders(string $headerString): array
    {
        $headers = [];
        foreach (explode("\r\n", $headerString) as $line) {
            if (strpos($line, ':') !== false) {
                list($key, $value) = explode(':', $line, 2);
                $headers[trim($key)] = trim($value);
            }
        }
        return $headers;
    }

    /**
     * getLastResponseSignature returns the last response signature for verification
     */
    public function getLastResponseSignature(): ?string
    {
        return $this->lastResponseHeaders['Rest-Sign'] ?? null;
    }

    /**
     * getLastStatusCode returns the last HTTP status code
     */
    public function getLastStatusCode(): int
    {
        return $this->lastStatusCode;
    }

    /**
     * getRetryAfter returns the Retry-After header value (for rate limiting)
     */
    public function getRetryAfter(): ?int
    {
        $value = $this->lastResponseHeaders['Retry-After'] ?? null;
        return $value !== null ? (int) $value : null;
    }

    /**
     * getLastErrorCode returns the API error code from the last failed request
     */
    public function getLastErrorCode(): ?string
    {
        return $this->lastErrorCode;
    }

    /**
     * isRateLimited checks if the last error was a rate limit error
     */
    public function isRateLimited(): bool
    {
        return $this->lastErrorCode === 'rate_limited' || $this->lastStatusCode === 429;
    }

    /**
     * isAuthError checks if the last error was an authentication error
     */
    public function isAuthError(): bool
    {
        return $this->lastErrorCode === 'invalid_auth' || $this->lastStatusCode === 401;
    }

    /**
     * isValidationError checks if the last error was a validation error
     */
    public function isValidationError(): bool
    {
        return $this->lastErrorCode === 'validation_error' || $this->lastStatusCode === 422;
    }
}

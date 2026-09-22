# HTTP requests

`October\Rain\Network\Http` verifies HTTPS certificates and hostnames by default.
Requests to servers with untrusted certificates will fail TLS verification.
For a private certificate authority, configure its CA bundle on the request:

```php
use October\Rain\Network\Http;

$response = Http::get('https://internal.example/resource', function ($http) {
    $http->setOption(CURLOPT_CAINFO, '/path/to/private-ca.pem');
});
```

`verifySSL()` explicitly enables peer and hostname verification and remains
available for existing callers. Custom cURL options are applied after defaults.
A caller that deliberately needs insecure TLS can explicitly set
`CURLOPT_SSL_VERIFYPEER` to `false` and `CURLOPT_SSL_VERIFYHOST` to `0`; this disables
server identity checks and should not be used for sensitive traffic.

These defaults apply to Rain's `October\Rain\Network\Http` client, independently
of Laravel's HTTP client and its `Http` facade.

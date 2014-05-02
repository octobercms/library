## Rain Network Services

Provides classes used for opening sockets across the network.

### HTTP Protocol

Used as a cURL wrapper for the HTTP protocol.

**Basic usage**

    // Make a GET call
    Http::get('http://octobercms.com');

    // Make a POST call
    Http::post('...');

    // Make a DELETE call
    Http::delete('...');

    // Make a PATCH call
    Http::patch('...');

    // Make a PUT call
    Http::put('...');

    // Make a OPTIONS call
    Http::options('...');

**Expected response**

    $result = Http::post('http://octobercms.com');
    echo $result;                          // Outputs: <html><head><title>...
    echo $result->code;                    // Outputs: 200
    echo $result->headers['Content-Type']; // Outputs: text/html; charset=UTF-8

**Advanced usage**

    Http::post('http://octobercms.com', function($http){

       // Sets a HTTP header
       $http->header('Rest-Key', '...');

       // Set a proxy of type (http, socks4, socks5)
       $http->proxy('type', 'host', 'port', 'username', 'password');

       // Use basic authentication
       $http->auth('user', 'pass');

       // Sends data with the request
       $http->data('foo', 'bar');
       $http->data(['key' => 'value', ...]);

       // Disable redirects
       $http->noRedirect();

       // Check host SSL certificate
       $http->verifySSL();

       // Sets the timeout duration
       $http->timeout(3600);

       // Write response to a file
       $http->toFile('some/path/to/a/file.txt');

       // Sets a cURL option manually
       $http->setOption('CURLOPT_SSL_VERIFYHOST', false);

    });
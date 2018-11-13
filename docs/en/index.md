Extension
---
```yaml
extensions:
	httpClient: Fapi\HttpClient\DI\HttpClientExtension
```

Configuration options
---------------------

The default configuration options are

```yaml
httpClient:
	type: guzzle
	logging: false
```

Option `type` specifies the type of HTTP client to be used. Supported types are `guzzle` and `curl`.

Option `logging` enables logging of all HTTP requests, HTTP responses and HTTP client errors.

Making HTTP requests
--------------------

Downloading Google homepage is as easy as

```php
$httpRequest = new HttpRequest('https://www.google.com/');
$httpResponse = $httpClient->sendHttpRequest($httpRequest);
```

The HTTP response object has methods for retrieving status code, response headers and response body.

HTTP request options
--------------------

Option `form_params` can be used for making POST request with `Content-Type: application/x-www-form-urlencoded`.

```php
$httpRequest = new HttpRequest('https://www.example.com/login', HttpMethod::POST, [
	'form_params' => [
		'username' => 'admin',
		'password' => 'xxx',
	],
]);
```

Option `headers` can be used for specifying HTTP request headers.

```php
$httpRequest = new HttpRequest('https://www.google.com/', HttpMethod::GET, [
	'headers' => [
		'User-Agent' => 'Bot/1.0',
	],
]);
```

Option `auth` can be used for specifying credentials for HTTP basic authentication.

```php
$httpRequest = new HttpRequest('https://www.example.com/private', HttpMethod::GET, [
	'auth' => ['admin', 'xxx'],
]);
```

Option `body` can be used for specifying request body.

```php
$httpRequest = new HttpRequest('https://www.example.com/api', HttpMethod::POST, [
	'body' => 'Request body',
]);
```

Option `json` can be used for specifying request body data, which should be serialized to JSON before sending.

```php
$httpRequest = new HttpRequest('https://www.example.com/api', HttpMethod::POST, [
	'json' => [
		'foo' => 'bar',
	],
]);
```

Option `cookies` can be used for specifying a cookie jar. This option is only supported by the Guzzle HTTP client.

```php
$cookieJar = new GuzzleHttp\Cookie\CookieJar();
$httpRequest = new HttpRequest('https://www.example.com/', HttpMethod::GET, [
	'cookies' => $cookieJar,
]);
```

Option `connect_timeout` can be used for specifing connection time limit.

```php
$httpRequest = new HttpRequest('https://www.example.com/', HttpMethod::GET, [
	'connect_timeout' => 3,
]);
```

Option `timeout` can be used for specifing response time limit.

```php
$httpRequest = new HttpRequest('https://www.example.com/', HttpMethod::GET, [
	'timeout' => 3,
]);
```

Redirects
---------

If you want to follow redirects, use RedirectHelper after sending HTTP request.

```php
$httpResponse = $httpClient->sendHttpRequest($httpRequest);
$httpResponse = RedirectHelper::followRedirects($httpClient, $httpResponse);
```

Mocking HTTP clients
--------------------

Let's assume you want to test class `Foo` which uses an HTTP client.

```php
namespace SampleProjectTests;

use Fapi\HttpClient\CapturingHttpClient;
use Fapi\HttpClient\GuzzleHttpClient;
use SampleProject\Foo;
use SampleProjectTests\MockHttpClients\FooMockHttpClient;
use Tester\Assert;
use Tester\TestCase;


class FooTest extends TestCase
{
	/**
	 * @var bool
	 */
	private $generateMockHttpClient = true;

	/**
	 * @var CapturingHttpClient|FooMockHttpClient
	 */
	private $httpClient;


	public function __construct()
	{
		if ($this->generateMockHttpClient) {
			$this->httpClient = new CapturingHttpClient(new GuzzleHttpClient());
		} else {
			$this->httpClient = new FooMockHttpClient();
		}
	}


	public function __destruct()
	{
		if ($this->generateMockHttpClient) {
			$this->httpClient->writeToPhpFile(
				__DIR__ . '/MockHttpClients/FooMockHttpClient.php',
				'SampleProjectTests\MockHttpClients\FooMockHttpClient'
			);
		}
	}


	public function testDownloadData()
	{
		$foo = new Foo($this->httpClient);
		Assert::same(42, $foo->downloadData());
	}
}
```

After the test succeeds, you can switch the `$generateMockHttpClient` property to `false`. Whenever you run the test next time, it will be tested against the mock HTTP client.

You can always update the mock HTTP client by setting the property back to `true` and running the test.

It is recommended to commit passing test cases with `$generateMockHttpClient` set to `false`. The tests will then be deterministic. You will be able to run them even without an Intenet connection or when the remote service is down. Furthermore, they will run much faster.

REST HTTP client
----------------

You can use class `RestClient` for accessing JSON REST APIs protected by HTTP basic authentication. It provides methods for creating, getting, updating and deleting resources.

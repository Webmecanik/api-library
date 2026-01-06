# Dependencies
* PHP 8.0 is now the minimum (was PHP 7.2 in 3.x)
* We are decoupled from any HTTP messaging client with the help of [PSR-18 HTTP Client](https://www.php-fig.org/psr/psr-18/). This requires an extra package providing [psr/http-client-implementation](https://packagist.org/providers/psr/http-client-implementation). To use Guzzle 7, for example, simply require `guzzlehttp/guzzle` in your project. If you do not have an HTTP client installed, `php-http/discovery` will install one if you allow the Composer plugin.

## Installation Change
```bash
# Old (3.x) - cURL was built-in
composer require webmecanik/api-library

# New (4.x) - HTTP client required
composer require webmecanik/api-library guzzlehttp/guzzle:^7.3
```

# Api
* \Mautic\Api\Api::getLogger now returns void as the LoggerAwareInterface dictates
* \Mautic\Api\Api::getResponseInfo and \Mautic\Response::getInfo have been removed, all Auth classes now have a getResponse method to get the PSR-7 response message

# HTTP
## Timeout
The setCurlTimeout-method (`$auth->setCurlTimeout(10);`) has been removed. If you like to set the timeout, you should configure your HTTP client to do so. For example, with Guzzle 7, you can do this:

```php
$httpClient = new \GuzzleHttp\Client([
    'timeout'  => 10,
]);
$settings = [
    // ...
];

$initAuth = new \Mautic\Auth\ApiAuth($httpClient);
$auth = $initAuth->newAuth($settings);
```

# TwoLeggedOAuth2 (Client Credentials)

The TwoLeggedOAuth2 authentication has been updated for PSR-18 compatibility.

## Method Changes
* `getAccessToken()` now automatically calls `requestAccessToken()` if token is missing/expired
* New method `requestAccessToken()` explicitly requests a new token
* New method `validateAccessToken()` checks if current token is valid
* New method `getAccessTokenData()` returns token data for caching
* New method `accessTokenUpdated()` indicates if token was refreshed

## Usage Change
```php
// Old (3.x)
$auth = $initAuth->newAuth($settings, 'TwoLeggedOAuth2');
$token = $auth->getAccessToken(); // Always made HTTP request

// New (4.x)
$auth = $initAuth->newAuth($settings, 'TwoLeggedOAuth2');
if (!$auth->isAuthorized()) {
    $auth->requestAccessToken();
}
// Or simply:
$token = $auth->getAccessToken(); // Only requests if needed
```

# New Features in 4.x

## Point Groups API (Mautic 5.x)
New `PointGroups` context and contact methods for point group scoring:
```php
$contactApi->getPointGroupScores($contactId);
$contactApi->addPointGroupScore($contactId, $groupId, $points);
$contactApi->subtractPointGroupScore($contactId, $groupId, $points);
```

## Send Custom Email to Contact
New method to send custom emails without pre-defined templates:
```php
$emailApi->sendCustomToContact($contactId, [
    'fromEmail' => 'noreply@example.com',
    'fromName'  => 'My App',           // optional
    'subject'   => 'Hello',
    'content'   => '<p>Custom HTML content with {{contactfield=firstname}}</p>',
]);
```

**Note:** This feature requires Mautic with [PR #12854](https://github.com/mautic/mautic/pull/12854) merged.
It is not available in standard Mautic 5.x releases.

# Version Compatibility

| Library Version | PHP | Mautic |
|-----------------|-----|--------|
| 3.x | 7.2+ | 3.x, 4.x |
| 4.x | 8.0+ | 4.x, 5.x |

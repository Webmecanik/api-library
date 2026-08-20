<?php

/*
 * @copyright   2026 Webmecanik. All rights reserved.
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Auth;

use Mautic\Exception\IncorrectParametersReturnedException;
use Mautic\Exception\RequiredParameterMissingException;

/**
 * OAuth2 Client Credentials, also known as 2-legged OAuth2.
 *
 * Server-to-server authentication: the application authenticates with its own credentials,
 * no end user and no redirect are involved. Mautic supports this grant since 4.x but the
 * upstream library only implements authorization_code and refresh_token.
 *
 * Note on getQueryParameters(): this class deliberately does NOT override it. The inherited
 * implementation appends nothing to the query string of a POST, which is what we want: the
 * access token travels in the Authorization header only. Sending it in both the header and
 * the query string makes Mautic's OAuth2::getBearerToken() find several tokens and reject
 * the request, which surfaces as an opaque HTTP 500 on multipart uploads.
 *
 * @see https://developer.mautic.org/#client-credentials
 */
class TwoLeggedOAuth2 extends AbstractAuth
{
    private const TOKEN_ENDPOINT = '/oauth/v2/token';

    /**
     * A token expiring within this many seconds is already treated as stale.
     */
    private const EXPIRY_BUFFER_SECONDS = 10;

    private string $clientId = '';

    private string $clientSecret = '';

    private string $accessTokenUrl = '';

    private ?string $accessToken = null;

    /**
     * Unix timestamp at which the access token expires.
     */
    private ?int $accessTokenExpires = null;

    /**
     * ApiAuth::newAuth() resolves these arguments by reflecting on their names, so they must
     * match the keys of the settings array handed to it.
     *
     * @throws RequiredParameterMissingException
     */
    public function setup(
        ?string $baseUrl = null,
        ?string $clientKey = null,
        ?string $clientSecret = null,
        ?string $accessToken = null,
        ?int $accessTokenExpires = null,
    ): void {
        $clientKey    = is_null($clientKey) ? '' : trim($clientKey);
        $clientSecret = is_null($clientSecret) ? '' : trim($clientSecret);
        $baseUrl      = is_null($baseUrl) ? '' : trim($baseUrl);

        if ('' === $clientKey || '' === $clientSecret) {
            $this->log('parameters did not include clientKey and/or clientSecret');

            throw new RequiredParameterMissingException('One or more required parameters was not supplied. Both clientKey and clientSecret required!');
        }

        if ('' === $baseUrl) {
            $this->log('parameters did not include baseUrl');

            throw new RequiredParameterMissingException('One or more required parameters was not supplied. baseUrl required!');
        }

        $this->clientId           = $clientKey;
        $this->clientSecret       = $clientSecret;
        $this->accessTokenUrl     = rtrim($baseUrl, '/').self::TOKEN_ENDPOINT;
        $this->accessToken        = $accessToken;
        $this->accessTokenExpires = $accessTokenExpires;
    }

    public function isAuthorized(): bool
    {
        return $this->validateAccessToken();
    }

    /**
     * True when an access token is held and is not about to expire.
     */
    public function validateAccessToken(): bool
    {
        if (is_null($this->accessToken) || '' === $this->accessToken) {
            $this->log('no access token');

            return false;
        }

        if (!is_null($this->accessTokenExpires) && $this->accessTokenExpires < (time() + self::EXPIRY_BUFFER_SECONDS)) {
            $this->log('access token expired');

            return false;
        }

        return true;
    }

    /**
     * Exchange the client credentials for a fresh access token.
     *
     * @throws IncorrectParametersReturnedException
     */
    public function requestAccessToken(): void
    {
        // Drop the held token first so a stale one is not sent as a Bearer header on this call.
        $this->accessToken        = null;
        $this->accessTokenExpires = null;

        $response = $this->makeRequest($this->accessTokenUrl, [
            'client_id'     => $this->clientId,
            'client_secret' => $this->clientSecret,
            'grant_type'    => 'client_credentials',
        ], 'POST');

        if (!is_array($response) || !isset($response['access_token'], $response['expires_in'])) {
            $this->log('response did not have an access token');

            throw new IncorrectParametersReturnedException('Incorrect access token parameters returned: '.$this->describeFailure($response));
        }

        $this->accessToken        = (string) $response['access_token'];
        $this->accessTokenExpires = time() + (int) $response['expires_in'];
    }

    /**
     * @throws IncorrectParametersReturnedException
     */
    public function getAccessToken(): string
    {
        if (false === $this->validateAccessToken()) {
            $this->requestAccessToken();
        }

        return (string) $this->accessToken;
    }

    /**
     * Unix timestamp at which the held access token expires, for callers that cache it.
     */
    public function getAccessTokenExpiration(): ?int
    {
        return $this->accessTokenExpires;
    }

    protected function prepareRequest($url, array $headers, array $parameters, $method, array $settings): array
    {
        if (!is_null($this->accessToken) && '' !== $this->accessToken) {
            $headers[] = 'Authorization: Bearer '.$this->accessToken;
        }

        return [$headers, $parameters];
    }

    private function describeFailure(mixed $response): string
    {
        if (is_array($response) && is_array($response['errors'] ?? null)) {
            $messages = [];
            foreach ($response['errors'] as $error) {
                if (is_array($error) && is_string($error['message'] ?? null)) {
                    $messages[] = $error['message'];
                }
            }

            if ([] !== $messages) {
                return implode('; ', $messages);
            }
        }

        return print_r($response, true);
    }
}

<?php

/*
 * @copyright   2021 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\Auth;

use Mautic\Exception\IncorrectParametersReturnedException;
use Mautic\Exception\RequiredParameterMissingException;

/**
 * OAuth2 Client Credentials (2-legged OAuth2) Authentication.
 *
 * This authentication method is used for server-to-server communication
 * where no user interaction is required. The application authenticates
 * using its own credentials (client_id and client_secret).
 *
 * @see https://developer.mautic.org/#client-credentials
 */
class TwoLeggedOAuth2 extends AbstractAuth
{
    /**
     * Consumer or client key.
     */
    protected string $_client_id;

    /**
     * Consumer or client secret.
     */
    protected string $_client_secret;

    /**
     * Access token returned by OAuth server.
     */
    protected ?string $_access_token = null;

    /**
     * Unix timestamp for when token expires.
     */
    protected ?int $_expires = null;

    /**
     * OAuth2 token type.
     */
    protected string $_token_type = 'bearer';

    /**
     * Set to true if access token was updated.
     */
    protected bool $_access_token_updated = false;

    /**
     * Access token URL.
     */
    protected string $_access_token_url;

    /**
     * Check if the current access token is still valid.
     */
    public function isAuthorized(): bool
    {
        $this->log('isAuthorized()');

        return $this->validateAccessToken();
    }

    /**
     * Setup the authentication credentials.
     *
     * @param string|null $baseUrl            URL of the Mautic instance
     * @param string|null $clientKey          Client ID from Mautic API credentials
     * @param string|null $clientSecret       Client Secret from Mautic API credentials
     * @param string|null $accessToken        Previously stored access token (optional)
     * @param int|null    $accessTokenExpires Unix timestamp when token expires (optional)
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
        if (empty($clientKey) || empty($clientSecret)) {
            $this->log('parameters did not include clientKey and/or clientSecret');
            throw new RequiredParameterMissingException('One or more required parameters was not supplied. Both clientKey and clientSecret required!');
        }

        if (empty($baseUrl)) {
            $this->log('parameters did not include baseUrl');
            throw new RequiredParameterMissingException('One or more required parameters was not supplied. baseUrl required!');
        }

        $this->_client_id        = trim($clientKey);
        $this->_client_secret    = trim($clientSecret);
        $this->_access_token_url = rtrim($baseUrl, '/').'/oauth/v2/token';

        if (!empty($accessToken)) {
            $this->setAccessTokenDetails([
                'access_token' => $accessToken,
                'expires'      => $accessTokenExpires,
            ]);
        }
    }

    /**
     * Check if the access token was updated during the last request.
     */
    public function accessTokenUpdated(): bool
    {
        return $this->_access_token_updated;
    }

    /**
     * Get the current access token data.
     *
     * @return array{access_token: string|null, expires: int|null, token_type: string}
     */
    public function getAccessTokenData(): array
    {
        return [
            'access_token' => $this->_access_token,
            'expires'      => $this->_expires,
            'token_type'   => $this->_token_type,
        ];
    }

    /**
     * Set access token details from stored data.
     *
     * @param array{access_token?: string|null, expires?: int|null, token_type?: string} $accessTokenDetails
     *
     * @return $this
     */
    public function setAccessTokenDetails(array $accessTokenDetails): static
    {
        $this->_access_token = $accessTokenDetails['access_token'] ?? null;
        $this->_expires      = isset($accessTokenDetails['expires'])
            ? (int) $accessTokenDetails['expires']
            : null;

        if (isset($accessTokenDetails['token_type'])) {
            $this->_token_type = $accessTokenDetails['token_type'];
        }

        return $this;
    }

    /**
     * Validate if the current access token is still valid.
     */
    public function validateAccessToken(): bool
    {
        $this->log('validateAccessToken()');

        // Check if token is expired (with 10 second buffer)
        if (!empty($this->_access_token) && !empty($this->_expires)
            && $this->_expires < (time() + 10)) {
            $this->log('access token expired');

            return false;
        }

        if (!empty($this->_access_token)) {
            $this->log('has valid access token');

            return true;
        }

        $this->log('no access token');

        return false;
    }

    /**
     * Request a new access token using client credentials.
     *
     * @throws IncorrectParametersReturnedException
     */
    public function requestAccessToken(): bool
    {
        $this->log('requestAccessToken()');

        $parameters = [
            'client_id'     => $this->_client_id,
            'client_secret' => $this->_client_secret,
            'grant_type'    => 'client_credentials',
        ];

        $params = $this->makeRequest($this->_access_token_url, $parameters, 'POST');

        if (is_array($params)) {
            if (isset($params['access_token']) && isset($params['expires_in'])) {
                $this->log('access token set as '.$params['access_token']);

                $this->_access_token         = $params['access_token'];
                $this->_expires              = time() + (int) $params['expires_in'];
                $this->_token_type           = $params['token_type'] ?? 'bearer';
                $this->_access_token_updated = true;

                if ($this->_debug) {
                    $_SESSION['oauth']['debug']['tokens']['access_token'] = $params['access_token'];
                    $_SESSION['oauth']['debug']['tokens']['expires_in']   = $params['expires_in'];
                    $_SESSION['oauth']['debug']['tokens']['token_type']   = $params['token_type'] ?? null;
                }

                return true;
            }
        }

        $this->log('response did not have an access token');

        if ($this->_debug) {
            $_SESSION['oauth']['debug']['response'] = $params;
        }

        if (isset($params['errors'])) {
            $errors = [];
            foreach ($params['errors'] as $error) {
                $errors[] = $error['message'];
            }
            $response = implode('; ', $errors);
        } else {
            $response = print_r($params, true);
        }

        throw new IncorrectParametersReturnedException('Incorrect access token parameters returned: '.$response);
    }

    /**
     * Get the access token, requesting a new one if necessary.
     *
     * @throws IncorrectParametersReturnedException
     */
    public function getAccessToken(): string
    {
        if (!$this->validateAccessToken()) {
            $this->requestAccessToken();
        }

        return $this->_access_token;
    }

    protected function prepareRequest($url, array $headers, array $parameters, $method, array $settings): array
    {
        if (!empty($this->_access_token)) {
            $headers = array_merge($headers, ['Authorization: Bearer '.$this->_access_token]);
        }

        return [$headers, $parameters];
    }

    protected function getQueryParameters($isPost, $parameters): array
    {
        $query = parent::getQueryParameters($isPost, $parameters);

        // Support for file uploads - pass access token as query parameter
        if (isset($parameters['file'])) {
            $query['access_token'] = $this->_access_token;
        }

        return $query;
    }
}

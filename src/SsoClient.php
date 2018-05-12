<?php
/**
 * Copyright (c) 2018, Prometheus Satyen
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
 * documentation files (the "Software"), to deal in the Software without restriction, including without limitation
 * the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
 * and to permit persons to whom the Software is furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
 * Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
 * WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
 * COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
 * OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */

namespace Swag;

use Swag\Exceptions\AuthorizationFailureException;
use Swag\Exceptions\InvalidTokenException;
use Swag\Exceptions\RefreshFailureException;
use Swag\Sso\TokenInterface;
use Swag\Sso\TokenPair;

use GuzzleHttp\Client as GuzzleClient;
use Exception;

class SsoClient {

    const BASE_URI = 'https://login.eveonline.com/oauth/';

    private $clientId;
    private $clientSecret;

    public function __construct()
    {
        $this->clientId = Config::get('eve_client_id');
        $this->clientSecret = Config::get('eve_client_secret');
    }

    /**
     * Generates the authorization url to redirect users to in order to begin a login
     *
     * @param string[] $scopes array of string scopes to request
     * @param string $callback callback url which sso will redirect back to once the user has completed authorization
     * @param string $state sso state parameter
     * @return string authorization url to redirect users to
     */
    public function redirect($scopes, $callback, $state = 'login')
    {
        $data = [
            'response_type' => 'code',
            'redirect_uri' => $callback,
            'client_id' => $this->clientId,
            'scope' => implode(' ', $scopes),
            'state' => $state
        ];

        return $this->constructUrl('authorize', $data);
    }

    /**
     * Exchanges an authorization code for tokens
     *
     * @param string $code authorization code
     * @param string[] $requiredScopes if provided, the scopes authorized by the user will be validated against the scopes
     * in this array to ensure the user did not interfere with the request
     * @return TokenPair tokens
     * @throws AuthorizationFailureException
     */
    public function authorize($code, $requiredScopes = []) {
        $client = new GuzzleClient();
        $res = $client->post($this->constructUrl('token'), [
            'auth' => [$this->clientId, $this->clientSecret],
            'form_params' => [
                'grant_type' => 'authorization_code',
                'code' => $code
            ],
        ]);

        $data = json_decode($res->getBody()->getContents());

        if ((!$data) || (!isset($data->access_token))) {
            throw new AuthorizationFailureException();
        }

        $token = new TokenPair(
            $data->access_token,
            time() + $data->expires_in,
            $data->refresh_token
        );

        $info = $this->verify($token);

        if (!$info) {
            throw new AuthorizationFailureException();
        }

        $token->characterId = $info->characterId;
        $token->scopes = $info->scopes;

        $diff = array_diff($requiredScopes, $token->scopes);
        if ($diff) {
            throw new AuthorizationFailureException();
        }

        return $token;
    }

    /**
     * Attempts to use a refresh token to obtain a new access token
     *
     * @param TokenPair $token token object
     * @return TokenPair token object with access token refreshed
     * @throws RefreshFailureException
     */
    public function refresh(TokenPair $token) {

        $client = new GuzzleClient();
        try {
            $res = $client->post($this->constructUrl('token'), [
                'auth' => [$this->clientId, $this->clientSecret],
                'form_params' => [
                    'grant_type' => 'refresh_token',
                    'refresh_token' => $token->refreshToken
                ],
            ]);
        } catch (Exception $e) {
            throw new RefreshFailureException();
        }

        $data = json_decode($res->getBody()->getContents());

        if ((!$data) || (!isset($data->access_token))) {
            throw new RefreshFailureException();
        }

        $token->accessToken = $data->access_token;
        $token->accessTokenExpiry = time() + $data->expires_in;

        return $token;
    }

    /**
     * Verify a token and fetch some basic information regarding it
     *
     * @param TokenInterface $token token object
     * @return object basic token information
     * @throws InvalidTokenException
     */
    public function verify(TokenInterface $token) {
        $client = new GuzzleClient();
        $res = $client->get($this->constructUrl('verify'), [
            'headers' => [
                'Authorization' => 'Bearer ' . $token->getToken()
            ]
        ]);

        $data = json_decode($res->getBody()->getContents());

        if ((!$data) || (!isset($data->CharacterID))) {
            throw new InvalidTokenException();
        }

        return (object) [
            'characterId' => $data->CharacterID,
            'characterName' => $data->CharacterName,
            'scopes' => explode(' ', $data->Scopes),
            'type' => $data->TokenType,
            'characterOwnerHash' => $data->CharacterOwnerHash
        ];
    }

    /**
     * Construct a request URL
     *
     * @param string $endpoint
     * @param array $query
     * @param boolean $encode
     * @return string request URL
     */
    private function constructUrl($endpoint, $query = null, $encode = true)
    {
        $baseUri = rtrim(self::BASE_URI, '/');
        $endpoint = trim($endpoint, '/');
        if ($query) {
            $encoded = http_build_query($query);
            return $baseUri . '/' . $endpoint . '/?' . ($encode ? $encoded : urldecode($encoded));
        } else {
            return $baseUri . '/' . $endpoint . '/';
        }
    }
}
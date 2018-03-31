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

namespace Swag\Sso;

use Swag\Exceptions\InvalidTokenException;

class Token implements TokenInterface
{
    /**
     * @var string $accessToken access token
     */
    public $accessToken;

    public function __construct($accessToken)
    {
        $this->accessToken = $accessToken;
    }

    public function getToken()
    {
        return $this->accessToken;
    }

    /**
     * Return the character's ID on the EVE servers
     *
     * This generic implementation will work but requires a query to the SSO server, where possible subclasses
     * should try to remove the dependency on this implementation
     *
     * @return int character id
     * @throws InvalidTokenException
     */
    public function getCharacterId()
    {
        try {
            return $this->verify()->characterId;
        } catch (InvalidTokenException $e) {
            throw $e;
        }
    }

    /**
     * Verifies the access token's validity and returns some basic information about it
     *
     * @return object access token basic information
     * @throws InvalidTokenException
     */
    public function verify()
    {
        $client = new SsoClient();
        try {
            return $client->verify($this);
        } catch (InvalidTokenException $e) {
            throw $e;
        }
    }
}
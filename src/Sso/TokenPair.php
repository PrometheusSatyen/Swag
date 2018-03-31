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

use Swag\SsoClient;

class TokenPair extends Token implements TokenInterface
{
    /**
     * @var int $accessTokenExpiry access token expiry as number of seconds since unix epoch
     */
    public $accessTokenExpiry;

    /**
     * @var string $refreshToken refresh token
     */
    public $refreshToken;

    /**
     * @var int $characterId character id this token is for
     */
    public $characterId;

    /**
     * @var string[] $scopes array of string scopes which this token has granted
     */
    public $scopes;

    public function __construct($accessToken, $accessTokenExpiry, $refreshToken, $characterId = null, $scopes = [])
    {
        $this->accessToken = $accessToken;
        $this->accessTokenExpiry = $accessTokenExpiry;
        $this->refreshToken = $refreshToken;
        $this->characterId = $characterId;
        $this->scopes = $scopes;
    }

    public function getToken()
    {
        if ($this->accessTokenExpiry < time() + 30) { // at least 30 seconds more of validity
            $this->refresh();
        }

        return parent::getToken();
    }

    public function getCharacterId()
    {
        return $this->characterId ?: parent::getCharacterId();
    }

    private function refresh()
    {
        $client = new SsoClient();
        $client->refresh($this);
    }
}
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

use Swag\Cache\Cache;
use Swag\Exceptions\ErrorLimitReachedException;
use Swag\Exceptions\InsufficientScopesException;
use Swag\Exceptions\InvalidTokenException;
use Swag\Exceptions\ServiceUnavailableException;
use Swag\Exceptions\TokenRequiredException;
use Swag\Logger\LoggerManager;
use Swag\Request\Request;
use Swag\Response\Response;
use Swag\Sso\TokenInterface;

use DateTime;
use DateTimeZone;

class EsiClient
{
    const BASE_URI = 'https://esi.tech.ccp.is/';
    const DEFAULT_DATA_SOURCE = 'tranquility';

    /**
     * @var TokenInterface
     */
    private $token = null;

    /**
     * @var string data source
     */
    private $dataSource;

    /**
     * Client constructor.
     *
     * @param array $options client options
     */
    public function __construct($options = [])
    {
        $this->dataSource = $options['data_source'] ?? self::DEFAULT_DATA_SOURCE;
    }

    /**
     * Log this client into ESI using the provided Token object
     *
     * @param TokenInterface $token
     */
    public function authenticate(TokenInterface $token)
    {
        $this->token = $token;
    }

    public function get($endpoint, $version, $options = [])
    {
        $cache = new Cache();

        $signature = hash('sha256', 'GET' . $endpoint . $version . json_encode($options) .
            (($this->token) ? $this->token->getCharacterId() : '') . $this->dataSource);

        if ((!isset($options['no_cache']) || !$options['no_cache']) && $cache->exists('req_' . $signature)) {
            return unserialize($cache->get('req_' . $signature));
        } else {
            $res = $this->request('GET', $endpoint, $version, $options);

            if ($res->hasHeader('Cache-Control')) {
                $cacheControl = $res->getHeader('Cache-Control');

                if ($cacheControl[0] == 'private') {
                    $res->cache($signature);
                } else if ($cacheControl[0] == 'public') { // Always disregard the character if cache control is public
                    $res->cache(hash('sha256', 'GET' . $endpoint . $version . json_encode($options) . $this->dataSource));
                }
            }

            return $res;
        }
    }

    public function post($endpoint, $version, $options = [])
    {
        return $this->request('POST', $endpoint, $version, $options);
    }

    public function put($endpoint, $version, $options = [])
    {
        return $this->request('PUT', $endpoint, $version, $options);
    }

    public function delete($endpoint, $version, $options = [])
    {
        return $this->request('DELETE', $endpoint, $version, $options);
    }

    /**
     * Send request
     *
     * @param string $method GET/POST/PUT/DELETE
     * @param string $endpoint
     * @param string $version
     * @param array $options
     * @return Response
     * @throws ErrorLimitReachedException
     * @throws InsufficientScopesException
     * @throws InvalidTokenException
     * @throws ServiceUnavailableException
     * @throws TokenRequiredException
     */
    private function request($method, $endpoint, $version, $options = [])
    {
        if ($this->isDowntime()) {
            throw new ServiceUnavailableException();
        }

        if ($this->isErrorLimited()) {
            throw new ErrorLimitReachedException();
        }

        if (!array_key_exists('query', $options)) {
            $options['query'] = [];
        }

        $options['query']['datasource'] = $this->dataSource;

        $req = new Request(
            $method,
            $this->constructUrl($endpoint, $version),
            $options,
            ($this->token) ? $this->token->getToken() : null
        );

        $res = $req->send();

        $logger = LoggerManager::get();
        $logger->log(
            $method,
            $this->constructUrl($endpoint, $version),
            $res->getStatusCode(),
            $res->getHeader('X-Esi-Error-Limit-Remain')[0]
        );

        if ($res->getStatusCode() >= 400 && $res->getStatusCode() < 600) {
            $cache = new Cache();
            $cache->set('esi_error_limit_remain', $res->getHeader('X-Esi-Error-Limit-Remain')[0]);
            $cache->expireat('esi_error_limit_remain', time() + $res->getHeader('X-Esi-Error-Limit-Reset')[0]);
        }

        switch($res->getStatusCode()) {
            case 401:
                if ($this->token) {
                    throw new InsufficientScopesException();
                } else {
                    throw new TokenRequiredException();
                }
                break;
            case 403:
                if ($this->token) {
                    throw new InvalidTokenException();
                } else {
                    throw new TokenRequiredException();
                }
                break;
            case 420:
                throw new ErrorLimitReachedException();
                break;
            case 502:
                throw new ServiceUnavailableException();
                break;
            case 503:
                throw new ServiceUnavailableException();
                break;
        }

        return $res;
    }

    /**
     * Check if we are currently in EVE downtime
     *
     * We assume downtime will be ~8 minutes
     */
    private function isDowntime()
    {
        $tz = new DateTimeZone('UTC');
        $dateTime = new DateTime('now', $tz);

        return ($dateTime->format('G') == 11) && ($dateTime->format('i') < 8);
    }

    public function isErrorLimited()
    {
        $cache = new Cache();
        if ($cache->exists('esi_error_limit_remain')) {
            return $cache->get('esi_error_limit_remain') < 1;
        } else {
            return false;
        }
    }

    /**
     * Construct a request URL
     *
     * @param string $endpoint
     * @param string $version
     * @return string request URL
     */
    private function constructUrl($endpoint, $version)
    {
        $baseUri = rtrim(self::BASE_URI, '/');
        $version = trim($version, '/');
        $endpoint = trim($endpoint, '/');
        return $baseUri . '/' . $version . '/' . $endpoint . '/';
    }
}

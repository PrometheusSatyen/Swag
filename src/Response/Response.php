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

namespace Swag\Response;

use Swag\Cache\Cache;

use GuzzleHttp\Psr7\Response as GuzzleResponse;

use Serializable;

class Response implements Serializable
{
    protected $statusCode;
    protected $reasonPhrase;
    protected $headers = [];
    protected $rawBody;

    public function __construct(GuzzleResponse $guzzleResponse)
    {
        $this->statusCode = $guzzleResponse->getStatusCode();
        $this->reasonPhrase = $guzzleResponse->getReasonPhrase();
        $this->headers = $guzzleResponse->getHeaders();
        $this->rawBody = $guzzleResponse->getBody()->getContents();
    }

    public function getStatusCode()
    {
        return $this->statusCode;
    }

    public function getReasonPhrase()
    {
        return $this->reasonPhrase;
    }

    public function hasHeader($name)
    {
        return array_key_exists($name, $this->headers);
    }

    public function getHeaders()
    {
        return $this->headers;
    }

    public function getHeader($name)
    {
        return $this->headers[$name];
    }

    public function getBody()
    {
        return json_decode($this->rawBody);
    }

    /**
     * Cache this response
     *
     * @param string $signature signature computed from the relevant parts of the request
     */
    public function cache($signature)
    {
        $cache = new Cache();
        $cache->set('req_' . $signature, serialize($this));
        $cache->expireat('req_' . $signature, strtotime($this->getHeader('Expires')[0]));
    }

    public function serialize()
    {
        return serialize([$this->statusCode, $this->reasonPhrase, $this->headers, $this->rawBody]);
    }

    public function unserialize($serialized)
    {
        list($this->statusCode, $this->reasonPhrase, $this->headers, $this->rawBody) = unserialize($serialized);
    }
}
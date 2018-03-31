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

namespace Swag\Request;

use Swag\Config;
use Swag\Response\Response;

use GuzzleHttp\Client as GuzzleClient;

class Request
{
    private $method;
    private $url;
    private $options;
    private $token;

    public function __construct($method = null, $url = null, $options = [], $token = null)
    {
        $this->method = $method;
        $this->url = $url;
        $this->options = $options;
        $this->token = $token;
    }

    public function setMethod($method)
    {
        $this->method = $method;
    }

    public function setUrl($url)
    {
        $this->url = $url;
    }

    public function setOptions($options)
    {
        $this->options = $options;
    }

    public function setToken($token)
    {
        $this->token = $token;
    }

    public function send()
    {
        $this->options['http_errors'] = false;
        $this->options['headers'] = [
            'User-Agent' => Config::get('user_agent'),
            'Accept' => 'application/json',
            'Content-Type' => 'application/json'
        ];

        if ($this->token) {
            $this->options['headers']['Authorization'] = 'Bearer ' . $this->token;
        }

        if (array_key_exists('body', $this->options)) {
            $this->options['body'] = json_encode($this->options['body']);
        };

        $client = new GuzzleClient();
        $res = $client->request($this->method, $this->url, $this->options);

        return new Response($res);
    }
}
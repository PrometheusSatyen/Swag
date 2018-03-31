# Swag

Swag is a library for interacting with the EVE ESI/SSO/etc. APIs.

It should be depended on via Composer.

Requirements
---------------------
* Redis

Usage
---------------------
```php
require_once('/vendor/autoload.php');

use Swag\EsiClient;
use Swag\Config;
use Swag\Sso\Token;

Config::set('eve_client_id', 'urgay');
Config::set('eve_client_secret', 'pwnd');

$client = new EsiClient();

$token = new Token('xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx');
$client->authenticate($token);

$response = $client->get("alliances", "v1");

foreach($response->getBody() as $allianceId) {
    echo "Alliance: {$allianceId}\n";
}
```

Request Options
---------------------
* **body** - array - properties to pass in the request to esi
* **no_cache** - bool - if set to true, will ignore any cached request and force request from ccp

Legal
---------------------
```
Copyright (c) 2018, Prometheus Satyen

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated
documentation files (the "Software"), to deal in the Software without restriction, including without limitation
the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software,
and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the
Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE
WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR
OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
```
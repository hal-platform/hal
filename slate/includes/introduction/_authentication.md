## Authentication

> Make sure to replace `beepboopbeepboop` with your API key.  
> To authorize a request, use the following code.

```http
GET /api HTTP/1.1
Accept: application/json
Host: hal9000
Authorization: token "beepboopbeepboop"
```

``` http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```php
<?php
// Guzzle 6
use GuzzleHttp\Client;

$token = 'beepboopbeepboop';

// Set the token in your default configuration for the guzzle client
$client = new Client([
    'base_uri' => 'https://hal9000',
    'headers' => [
        'Authentication' => sprintf('token %s', $token)
    ]
]);

$response = $client->get('/api');
```

```shell
# With shell, you can just pass the correct header with each request
curl "https://hal9000/api"
  -H "Authorization: token beepboopbeepboop"
```

Hal uses API tokens to allow access to the API. You can register a new Hal API key by signing into Hal and generating
a token from the [Settings](https://hal9000/settings) page. You may also revoke your tokens from this same page.

Hal expects the API token to be included in all **write** API requests. Reads do not require authentication.

`Authorization: token beepboopbeepboop`

<aside class="notice">
    You must replace <code>beepboopbeepboop</code> with your personal API token.
</aside>

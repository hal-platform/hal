# API Index

## Get Index

```http
GET /api HTTP/1.1
Accept: application/json
Host: hal.computer
Authorization: token "HAL_TOKEN"
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api');
```

```shell
curl "https://hal.computer/api"
```

> ### Response


```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "applications": {
            "href": "https://hal.computer/api/applications"
        },
        "organizations": {
            "href": "https://hal.computer/api/organizations"
        },
        "environments": {
            "href": "https://hal.computer/api/environments"
        },
        "servers": {
            "href": "https://hal.computer/api/servers"
        },
        "users": {
            "href": "https://hal.computer/api/users"
        },
        "queue": {
            "href": "https://hal.computer/api/queue"
        },
        "self": {
            "href": "https://hal.computer/api"
        }
    }
}
```

This endpoint retrieves the API index, which has links to other resources.

### HTTP Request

`GET https://hal.computer/api`

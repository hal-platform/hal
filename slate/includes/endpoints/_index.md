# API Index

## Get Index

```http
GET /api HTTP/1.1
Accept: application/json
Host: hal9000
Authorization: token "HAL_TOKEN"
```

``` http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal9000',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api');
```

```shell
curl "https://hal9000/api"
```

> ### Response

```json
{
  "_links": {
    "environments": {
      "href": "https://hal9000/api/environments"
    },
    "servers": {
      "href": "https://hal9000/api/servers"
    },
    "applications": {
      "href": "https://hal9000/api/applications"
    },
    "groups": {
      "href": "https://hal9000/api/groups"
    },
    "users": {
      "href": "https://hal9000/api/users"
    },
    "queue": {
      "href": "https://hal9000/api/queue"
    },
    "self": "https://hal9000/api"
  }
}
```

This endpoint retrieves the API index, which has links to other resources.

### HTTP Request

`GET https://hal9000/api`

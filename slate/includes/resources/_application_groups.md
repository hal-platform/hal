## Get All Groups

```http
GET /api/groups HTTP/1.1
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

$response = $client->get('/api/groups');
```

```shell
curl "https://hal9000/api/groups"
```

> ### Response

```json
{
    "_links": {
        "groups": [
            {
                "href": "https://hal9000/api/groups/12",
                "title": "sample-group"
            },
            {
                "href": "https://hal9000/api/groups/360",
                "title": "test-group"
            },
            {
                "href": "https://hal9000/api/groups/5",
                "title": "testing"
            }
        ],
        "self": "https://hal9000/api/groups"
    },
    "count": 3
}
```

Get all groups.

### HTTP Request

`GET https://hal9000/api/groups`

## Get Group

```http
GET /api/groups/5 HTTP/1.1
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

$response = $client->get('/api/groups/5');
```

```shell
curl "https://hal9000/api/groups/5"
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/groups/5",
            "title": "testing"
        }
    },
    "id": 1,
    "key": "testing",
    "name": "Example Application Group"
}
```

Get a specific group.

### HTTP Request

`GET https://hal9000/api/groups/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the group

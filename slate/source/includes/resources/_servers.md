# Servers

A **server** can either be an actual physical server as in the case of on-premise deployments,
or a **region** for aws-based deployments.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique server ID                                    | number   | `42`
type            | Type of server                                      | string   | `rsync`, `cd`, `eb`, `s3`, `script`
name            | Hostname or region, depending on server type        | string   | `localhost`, `us-east-1`
environment     | **Embedded** - Environment this server belongs to   | resource |
targets         | **Link** - List of targets for this server          | list     |

## Get All Servers

```http
GET /api/servers HTTP/1.1
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

$response = $client->get('/api/servers');
```

```shell
curl "https://hal.computer/api/servers"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
  "_links": {
    "next": {
        "href": "https://hal.computer/api/servers/page/2"
    },
    "last": {
        "href": "https://hal.computer/api/servers/page/2"
    },
    "servers": [
      {
        "href": "https://hal.computer/api/servers/1",
        "title": "qltestserver"
      },
      {
        "href": "https://hal.computer/api/servers/2",
        "title": "localhost"
      },
      {
        "href": "https://hal.computer/api/servers/3",
        "title": "test.example.com"
      },
      {
        "href": "https://hal.computer/api/servers/4",
        "title": "S3 (us-east-1)"
      },
      {
        "href": "https://hal.computer/api/servers/5",
        "title": "CD (us-east-1)"
      }
    ],
    "self": {
        "href": "https://hal.computer/api/servers"
    }
  },
  "count": 5,
  "total": 6,
  "page": 1
}
```

This endpoint retrieves all servers.

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 servers</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/servers(/page/{page})`

### URL Parameters

Parameter   | Description
----------- | -----------
page        | **Optional** - Page number to retrieve

## Get Server

```http
GET /api/servers/4 HTTP/1.1
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

$response = $client->get('/api/servers/4');
```

```shell
curl "https://hal.computer/api/servers/4"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": {
            "href": "https://hal.computer/api/servers/4",
            "title": "S3 (us-east-1)"
        },
        "targets": [
            {
                "href": "https://hal.computer/api/targets/100",
                "title": "S3 (bucket-name)"
            }
        ]
    },
    "_embedded": {
        "environment": {
            "_links": {
                "self": {
                    "href": "https://hal.computer/api/environments/2",
                    "title": "staging"
                }
            },
            "id": 2,
            "name": "beta",
            "is_production": false
        }
    },
    "id": 4,
    "type": "s3",
    "name": "us-east-1"
}
```

This endpoint retrieves a specific server.

### HTTP Request

`GET https://hal.computer/servers/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the server

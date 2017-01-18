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
deployments     | **Link** - List of deployments for this server      | list     |

## Get All Servers

```http
GET /api/servers HTTP/1.1
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

$response = $client->get('/api/servers');
```

```shell
curl "https://hal9000/api/servers"
```

> ### Response

```json
{
  "_links": {
    "next": {
        "href": "https://hal9000/api/servers/page/2"
    },
    "last": {
        "href": "https://hal9000/api/servers/page/2"
    },
    "servers": [
      {
        "href": "https://hal9000/api/servers/1",
        "title": "qltestserver"
      },
      {
        "href": "https://hal9000/api/servers/2",
        "title": "localhost"
      },
      {
        "href": "https://hal9000/api/servers/3",
        "title": "test.example.com"
      },
      {
        "href": "https://hal9000/api/servers/4",
        "title": "S3 (us-east-1)"
      },
      {
        "href": "https://hal9000/api/servers/5",
        "title": "CD (us-east-1)"
      }
    ],
    "self": "https://hal9000/api/servers"
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

`GET https://hal9000/api/servers(/page/{page})`

### URL Parameters

Parameter   | Description
----------- | -----------
page        | **Optional** - Page number to retrieve

## Get Server

```http
GET /api/servers/4 HTTP/1.1
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

$response = $client->get('/api/servers/4');
```

```shell
curl "https://hal9000/api/servers/4"
```

> ### Response

```json
{
  "_links": {
    "self": {
      "href": "https://hal9000/api/servers/4",
      "title": "S3 (us-east-1)"
    },
    "deployments": [
      {
        "href": "https://hal9000/api/deployments/100",
        "title": "S3 (bucket-name)"
      }
    ]
  },
  "_embedded": {
    "environment": {
      "_links": {
        "self": {
          "href": "https://hal9000/api/environments/2",
          "title": "beta"
        }
      },
      "id": 2,
      "name": "beta",
      "isProduction": false
    }
  },
  "id": 4,
  "type": "s3",
  "name": "us-east-1"
}
```

This endpoint retrieves a specific server.

### HTTP Request

`GET https://hal9000/servers/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the server

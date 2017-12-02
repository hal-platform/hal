# Groups

A **group** can either be an actual physical server as in the case of on-premise deployments,
or a **region** for aws-based deployments.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique group ID                                    | number   | `42`
type            | Type of group                                      | string   | `rsync`, `cd`, `eb`, `s3`, `script`
name            | Hostname or region, depending on group type        | string   | `localhost`, `us-east-1`
environment     | **Embedded** - Environment this group belongs to   | resource |
targets         | **Link** - List of targets for this group          | list     |

## Get All Groups

```http
GET /api/groups HTTP/1.1
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

$response = $client->get('/api/groups');
```

```shell
curl "https://hal.computer/api/groups"
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
        "href": "https://hal.computer/api/groups/page/2"
    },
    "last": {
        "href": "https://hal.computer/api/groups/page/2"
    },
    "groups": [
      {
        "href": "https://hal.computer/api/groups/1",
        "title": "qltestgroup"
      },
      {
        "href": "https://hal.computer/api/groups/2",
        "title": "localhost"
      },
      {
        "href": "https://hal.computer/api/groups/3",
        "title": "test.example.com"
      },
      {
        "href": "https://hal.computer/api/groups/4",
        "title": "S3 (us-east-1)"
      },
      {
        "href": "https://hal.computer/api/groups/5",
        "title": "CD (us-east-1)"
      }
    ],
    "self": {
        "href": "https://hal.computer/api/groups"
    }
  },
  "count": 5,
  "total": 6,
  "page": 1
}
```

This endpoint retrieves all groups.

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 groups</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/groups(/page/{page})`

### URL Parameters

Parameter   | Description
----------- | -----------
page        | **Optional** - Page number to retrieve

## Get Server

```http
GET /api/groups/4 HTTP/1.1
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

$response = $client->get('/api/groups/4');
```

```shell
curl "https://hal.computer/api/groups/4"
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
            "href": "https://hal.computer/api/groups/4",
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

This endpoint retrieves a specific group.

### HTTP Request

`GET https://hal.computer/groups/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the group

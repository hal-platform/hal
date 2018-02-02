# Queue

Endpoints useful for creating dashboards.

## Get active jobs in Queue

```http
GET /api/queue?since=2016-01-12T13:45:00Z HTTP/1.1
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

$response = $client->get('/api/queue?since=2016-01-12T13:45:00Z');
```

```shell
curl "https://hal.computer/api/queue?since=2016-01-12T13:45:00Z"
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
            "href": "https://hal.computer/api/queue?since=2016-01-12T13%3A45%3A00Z"
        },
        "refresh": {
            "href": "https://hal.computer/api/queue-refresh/build1+release2"
        }
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "release2"
                //release
            }
        ]
    },
    "count": 2
}
```

Get a list of active jobs. This list can contain both **Builds** and **Releases**.

### HTTP Request

`GET https://hal.computer/api/queue?since={since}`

### URL Parameters

Parameter | Description
--------- | -----------
since     | **Optional** - A valid ISO8601-formatted datetime such as `2016-01-12T13:45:00Z`. Jobs created after this time will be listed.

<aside class="notice">
    If not provided, the queue will show all jobs created in the previous <b>20 minutes</b>. Please note, the queue cannot retrieve jobs older than 3 days.
</aside>

<aside class="warning">
    The queue cannot retrieve jobs older than 3 days.
</aside>

### Embedded Resources

**Builds** and **Releases** are embedded in the queue. In addition, the following resources are embedded within their parent.

Parent          | Embedded Resource
--------------- | -----------------
build           | Application
release         | Application
release         | Build
release         | Deployment

## Get refreshed status of jobs

```http
GET /api/queue-refresh/build1+release2+build3 HTTP/1.1
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

$jobs = ['build1', 'release2', 'build3'];
$response = $client->get('/api/queue-refresh/' . implode(' ', $jobs));
```

```shell
curl "https://hal.computer/api/queue-refresh/build1+release2+build3"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": "https://hal.computer/api/queue?since=2016-01-12T13%3A45%3A00Z"
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "release2"
                //release
            }
        ]
    },
    "count": 2
}
```

Get the current status of jobs. This can be used to update the current status of jobs from the queue.

### HTTP Request

`GET https://hal.computer/api/queue-refresh/{jobs}`

### URL Parameters

Parameter | Description
--------- | -----------
jobs      | Space-delimited list of build or release IDs.

<aside class="warning">
    The queue cannot retrieve the current status of more than 50 jobs at once.
</aside>

### Embedded Resources

Unlike the **Queue** endpoint, the refresh endpoint only embeds the parent **builds** and **releases**.


## Get all jobs for a specific date

```http
GET /api/queue/date/2016-01-12 HTTP/1.1
Accept: application/json
Host: hal.computer
Authorization: token "HAL_TOKEN"
```

``` http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api/queue/date/2016-01-12');
```

```shell
curl "https://hal.computer/api/queue/date/2016-01-12"
```

> ### Response

```json
{
    "_links": {
        "self": "https://hal.computer/api/queue/date/2016-01-12"
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "release2"
                //release
            }
        ]
    },
    "count": 2
}
```

Get a list of jobs queued on the specified date. This list can contain both **Builds** and **Releases**.

### HTTP Request

`GET https://hal.computer/api/queue/date/{date}`

### URL Parameters

Parameter | Description
--------- | -----------
date      | A valid ISO8601-formatted date such as `2016-01-12`. Jobs created on this date will be listed.

### Embedded Resources

**Builds** and **Releases** are embedded in the queue. In addition, the following resources are embedded within their parent.

Parent          | Embedded Resource
--------------- | -----------------
build           | Application
release         | Application
release         | Build
release         | Deployment


## Get Build History for all applications

```http
GET /api/builds HTTP/1.1
Accept: application/json
Host: hal.computer
Authorization: token "HAL_TOKEN"
```

``` http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api/builds');
```

```shell
curl "https://hal.computer/api/builds"
```

> ### Response

```json
{
    "_links": {
        "next": {
            "href": "https://hal.computer/api/builds/page/2"
        },
        "last": {
            "href": "https://hal.computer/api/builds/page/3"
        },
        "builds": [
            {
                "href": "https://hal.computer/api/builds/d05e734a-9d3f-429f-afa7-9007f295ac1c",
                "title": "d05e734a-9d3f-429f-afa7-9007f295ac1c"
            },
            {
                "href": "https://hal.computer/api/builds/8ca89dee-7b9f-4721-9644-b1a1ea1856b3",
                "title": "8ca89dee-7b9f-4721-9644-b1a1ea1856b3"
            },
            {
                "href": "https://hal.computer/api/builds/ab041611-bbe3-4e62-ad9a-83a417938e2b",
                "title": "ab041611-bbe3-4e62-ad9a-83a417938e2b"
            },
            {
                "href": "https://hal.computer/api/builds/c54feb33-255a-43fe-9e54-c565a7fad0b5",
                "title": "c54feb33-255a-43fe-9e54-c565a7fad0b5"
            },
            {
                "href": "https://hal.computer/api/builds/7df9262f-50c9-4ddd-9440-093a8949f972",
                "title": "7df9262f-50c9-4ddd-9440-093a8949f972"
            }
            //additional builds pruned for brevity
        ],
        "self": "https://hal.computer/api/builds"
    },
    "count": 25,
    "total": 70,
    "page": 1
}
```

Get all builds.

Builds are listed in descending order, based on time they were created (page 3 builds are older than page 1).

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 builds</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/builds(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
page      | **Optional** - Page number to retrieve


## Get Release History for all applications

```http
GET /api/releases HTTP/1.1
Accept: application/json
Host: hal.computer
Authorization: token "HAL_TOKEN"
```

``` http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api/releases');
```

```shell
curl "https://hal.computer/api/releases"
```

> ### Response

```json
{
    "_links": {
        "next": {
            "href": "https://hal.computer/api/releases/page/2"
        },
        "last": {
            "href": "https://hal.computer/api/releases/page/3"
        },
        "releases": [
            {
                "href": "https://hal.computer/api/releases/f2afe003-46f4-4561-82c8-1c102096c583",
                "title": "f2afe003-46f4-4561-82c8-1c102096c583"
            },
            {
                "href": "https://hal.computer/api/releases/b770a726-19b4-49a7-9105-05f9d02ab58f",
                "title": "b770a726-19b4-49a7-9105-05f9d02ab58f"
            },
            {
                "href": "https://hal.computer/api/releases/f41d439c-bd5b-4e99-a8fd-f488155d522b",
                "title": "f41d439c-bd5b-4e99-a8fd-f488155d522b"
            },
            {
                "href": "https://hal.computer/api/releases/19010668-433f-4a19-8cb5-34fe5ba9b019",
                "title": "19010668-433f-4a19-8cb5-34fe5ba9b019"
            },
            {
                "href": "https://hal.computer/api/releases/b4dc963d-3cb2-47c6-90ab-527e6af03441",
                "title": "b4dc963d-3cb2-47c6-90ab-527e6af03441"
            }
            //additional releases pruned for brevity
        ]
        "self": "https://hal.computer/api/releases"
    },
    "count": 25,
    "total": 70,
    "page": 1
}
```

Get all releases.

Releases are listed in descending order, based on time they were created (page 3 releases are older than page 1).

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 releases</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/releases(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
page      | **Optional** - Page number to retrieve

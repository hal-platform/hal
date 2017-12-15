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
            "href": "https://localhost.hal/api/queue?since=2016-01-12T13%3A45%3A00Z"
        },
        "refresh": {
            "href": "https://localhost.hal/api/queue-refresh/build1+push2"
        }
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "push2"
                //push
            }
        ]
    },
    "count": 2
}
```

Get a list of active jobs. This list can contain both **Builds** and **Pushes**.

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

**Builds** and **Pushes** are embedded in the queue. In addition, the following resources are embedded within their parent.

Parent          | Embedded Resource
--------------- | -----------------
build           | Application
push            | Application
push            | Build
push            | Deployment

## Get refreshed status of jobs

```http
GET /api/queue-refresh/build1+push2+build3 HTTP/1.1
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

$jobs = ['build1', 'push2', 'build3'];
$response = $client->get('/api/queue-refresh/' . implode(' ', $jobs));
```

```shell
curl "https://hal.computer/api/queue-refresh/build1+push2+build3"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": "https://localhost.hal/api/queue?since=2016-01-12T13%3A45%3A00Z"
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "push2"
                //push
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
jobs      | Space-delimited list of build or push IDs.

<aside class="warning">
    The queue cannot retrieve the current status of more than 50 jobs at once.
</aside>

### Embedded Resources

Unlike the **Queue** endpoint, the refresh endpoint only embeds the parent **builds** and **pushes**.


## Get all jobs for a specific date

```http
GET /api/queue/date/2016-01-12 HTTP/1.1
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

$response = $client->get('/api/queue/date/2016-01-12');
```

```shell
curl "https://hal9000/api/queue/date/2016-01-12"
```

> ### Response

```json
{
    "_links": {
        "self": "https://localhost.hal/api/queue/date/2016-01-12"
    },
    "_embedded": {
        "jobs": [
            {
                "id": "build1"
                //build
            },
            {
                "id": "push2"
                //push
            }
        ]
    },
    "count": 2
}
```

Get a list of jobs queued on the specified date. This list can contain both **Builds** and **Pushes**.

### HTTP Request

`GET https://hal9000/api/queue/date/{date}`

### URL Parameters

Parameter | Description
--------- | -----------
date      | A valid ISO8601-formatted date such as `2016-01-12`. Jobs created on this date will be listed.

### Embedded Resources

**Builds** and **Pushes** are embedded in the queue. In addition, the following resources are embedded within their parent.

Parent          | Embedded Resource
--------------- | -----------------
build           | Application
push            | Application
push            | Build
push            | Deployment


## Get Build History for all applications

```http
GET /api/builds HTTP/1.1
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

$response = $client->get('/api/builds');
```

```shell
curl "https://hal9000/api/builds"
```

> ### Response

```json
{
    "_links": {
        "next": {
            "href": "https://hal9000/api/builds/page/2"
        },
        "last": {
            "href": "https://hal9000/api/builds/page/3"
        },
        "builds": [
            {
                "href": "https://hal9000/api/builds/b2.5KXYaoX",
                "title": "b2.5KXYaoX"
            },
            {
                "href": "https://hal9000/api/builds/b2.5KXaayW",
                "title": "b2.5KXaayW"
            },
            {
                "href": "https://hal9000/api/builds/b2.5KXQs4V",
                "title": "b2.5KXQs4V"
            },
            {
                "href": "https://hal9000/api/builds/b2.5KX7Pxg",
                "title": "b2.5KX7Pxg"
            },
            {
                "href": "https://hal9000/api/builds/b2.5yUBm4E",
                "title": "b2.5yUBm4E"
            }
            //additional builds pruned for brevity
        ],
        "self": "https://hal9000/api/builds"
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

`GET https://hal9000/api/builds(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
page      | **Optional** - Page number to retrieve


## Get Release History for all applications

```http
GET /api/releases HTTP/1.1
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

$response = $client->get('/api/releases');
```

```shell
curl "https://hal9000/api/releases"
```

> ### Response

```json
{
    "_links": {
        "next": {
            "href": "https://hal9000/api/releases/page/2"
        },
        "last": {
            "href": "https://hal9000/api/releases/page/3"
        },
        "releases": [
            {
                "href": "https://hal9000/api/releases/p2.5ty8Ump",
                "title": "p2.5ty8Ump"
            },
            {
                "href": "https://hal9000/api/releases/p2.5tsr91h",
                "title": "p2.5tsr91h"
            },
            {
                "href": "https://hal9000/api/releases/p2.5tqRjuq",
                "title": "p2.5tqRjuq"
            },
            {
                "href": "https://hal9000/api/releases/p2.5tqxUqB",
                "title": "p2.5tqxUqB"
            },
            {
                "href": "https://hal9000/api/releases/p2.5tq5nBL",
                "title": "p2.5tq5nBL"
            }
            //additional releases pruned for brevity
        ]
        "self": "https://hal9000/api/releases"
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

`GET https://hal9000/api/releases(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
page      | **Optional** - Page number to retrieve

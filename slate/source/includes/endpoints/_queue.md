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



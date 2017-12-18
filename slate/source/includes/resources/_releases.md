# Releases

A release is a deployment to a target. Builds are already built per environment, so a release is created by selecting
a build, and a deployment target within that environment.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique release ID                                   | string   | `p3.abcdef`
status          | Current status                                      | string   | `success`, `pending`, `running`
created         | Time release was created in ISO 8601                | string   | `2016-01-06T20:41:22Z`
start           | Time release was started in ISO 8601                | string   | `2016-01-06T20:42:00Z`
end             | Time release was finished in ISO 8601               | string   | `2016-01-06T20:45:36Z`
user            | **Link, Optional** - User that created release      | resource |
application     | **Link** - Application of build                     | resource |
target          | **Link** - Target deployed to                       | resource |
build           | **Link** - Build deployed                           | resource |
events          | **Link** - Events for release                       | list     |
page            | **Link** - Page in frontend UI for this release     |          |

## Get All Releases

```http
GET /api/applications/24/releases HTTP/1.1
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

$response = $client->get('/api/applications/24/releases');
```

```shell
curl "https://hal.computer/api/applications/24/releases"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "releases": [
            {
                "href": "https://hal.computer/api/releases/p2.5ty8Ump",
                "title": "p2.5ty8Ump"
            },
            {
                "href": "https://hal.computer/api/releases/p2.5tsr91h",
                "title": "p2.5tsr91h"
            },
            {
                "href": "https://hal.computer/api/releases/p2.5tqRjuq",
                "title": "p2.5tqRjuq"
            },
            {
                "href": "https://hal.computer/api/releases/p2.5tqxUqB",
                "title": "p2.5tqxUqB"
            },
            {
                "href": "https://hal.computer/api/releases/p2.5tq5nBL",
                "title": "p2.5tq5nBL"
            }
            //additional releases pruned for brevity
        ],
        "self": {
            "href": "https://hal.computer/api/applications/2/releases"
        }
    },
    "count": 13,
    "total": 13,
    "page": 1
}
```

Get all releases for an application.

Releases are listed in descending order, based on time they were created (page 3 releases are older than page 1).

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 releases</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/applications/{id}/releases(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application
page      | **Optional** - Page number to retrieve

## Get Release

```http
GET /api/releases/p2.5tqQFTF HTTP/1.1
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

$response = $client->get('/api/releases/p2.5tqQFTF');
```

```shell
curl "https://hal.computer/api/releases/p2.5tqQFTF"
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
            "href": "https://hal.computer/api/releases/p2.5tqQFTF",
            "title": "p2.5tqQFTF"
        },
        "user": {
            "href": "https://hal.computer/api/users/3001",
            "title": "SKluck"
        },
        "target": {
            "href": "https://hal.computer/api/targets/503",
            "title": "localhost"
        },
        "build": {
            "href": "https://hal.computer/api/builds/b2.5KXaayW",
            "title": "b2.5KXaayW"
        },
        "application": {
            "href": "https://hal.computer/api/applications/24",
            "title": "Hal Agent"
        },
        "events": {
            "href": "https://hal.computer/api/releases/p2.5tqQFTF/events"
        },
        "page": {
            "href": "https://hal.computer/releases/p2.5tqQFTF",
            "type": "text/html"
        }
    },
    "id": "p2.5tqQFTF",
    "status": "success",
    "created": "2015-02-16T17:35:03Z",
    "start": "2015-02-16T17:35:04Z",
    "end": "2015-02-16T17:35:07Z"
}
```

Get a release.

### HTTP Request

`GET https://hal.computer/api/releases/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the release

## Get Events for Release

```http
GET /api/releases/p2.5tqQFTF/events HTTP/1.1
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

$response = $client->get('/api/releases/p2.5tqQFTF/events');
```

```shell
curl "https://hal.computer/api/releases/p2.5tqQFTF/events"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "release": {
            "href": "https://hal.computer/api/releases/p2.5tqQFTF",
            "title": "p2.5tqQFTF"
        },
        "events": [
            {
                "href": "https://hal.computer/api/job-events/e34a3e76d2a44ff7a3c7",
                "title": "[1] Resolved release properties"
            },
            {
                "href": "https://hal.computer/api/job-events/d3c75e80d95a4d4b8681",
                "title": "[2] Copy archive to local storage"
            },
            {
                "href": "https://hal.computer/api/job-events/93ac503d0b334da5a41f",
                "title": "[3] Prepare release environment"
            },
            {
                "href": "https://hal.computer/api/job-events/e87ce283a6514f4a8215",
                "title": "[4] Code Deployment"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/releases/p2.5tqQFTF/events"
        }
    },
    "count": 4
}
```

Get Events for a release. By default this returns a list of links to events, however the client can choose to
embed events in the request.

<aside class="notice">
    Embedded events do not include context data. If you require event data, access each event resource individually.
</aside>

See [Events](#events) for more information about the **Job Event** resource.

### HTTP Request

`GET https://hal.computer/api/releases/{id}/events(?embed=events)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build
embed     | **Optional** - Should the events be embedded in this request?


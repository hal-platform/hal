# Builds

A build is a compiled application. To create a build, a user must choose an **environment**. Global builds (builds that
work in all environments) are not supported at this time, but likely will be in the future.

### Attributes

Attribute              | Description                                         | Type     | Example
---------------------- | --------------------------------------------------- | -------- | -------------
id                     | Unique build ID                                     | string   | `b2.abcdef`
status                 | Current status                                      | string   | `Success`, `Waiting`, `Error`
created                | Time build was created in ISO 8601                  | string   | `2016-01-06T20:41:22Z`
start                  | Time build was started in ISO 8601                  | string   | `2016-01-06T20:42:00Z`
end                    | Time build was finished in ISO 8601                 | string   | `2016-01-06T20:45:36Z`
reference              | Git ref of VCS source                               | resource | `master`, `tag/1.0`, `pull/4`
commit                 | Commit SHA of VCS source                            | resource | `3696a7a5e59eb435f3f67e34e6b4d456092565e8`
user                   | **Link, Optional** - User that created build        | resource |
application            | **Link** - Application of build                     | resource |
environment            | **Link** - Environment built                        | resource |
logs                   | **Link** - Event Logs for build                     | list     |
page                   | **Link** - Page in frontend UI for this build       |          |
start_push_page        | **Link** - Page to initiate push of this build      |          |
github_reference_page  | **Link** - GitHub page for the git reference        |          |
github_commit_page     | **Link** - GitHub page for the exact commit SHA     |          |

## Get All Builds

```http
GET /api/applications/24/builds HTTP/1.1
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

$response = $client->get('/api/applications/24/builds');
```

```shell
curl "https://hal9000/api/applications/24/builds"
```

> ### Response

```json
{
    "_links": {
        "next": {
            "href": "https://hal9000/api/applications/24/builds/page/2"
        },
        "last": {
            "href": "https://hal9000/api/applications/24/builds/page/3"
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
        "self": "https://hal9000/api/applications/24/builds"
    },
    "count": 25,
    "total": 70,
    "page": 1
}
```

Get all builds for an application.

Builds are listed in descending order, based on time they were created (page 3 builds are older than page 1).

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 builds</b>.
</aside>

### HTTP Request

`GET https://hal9000/api/applications/{id}/builds(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application
page      | **Optional** - Page number to retrieve

## Get Build

```http
GET /api/builds/b2.5KXaayW HTTP/1.1
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

$response = $client->get('/api/builds/b2.5KXaayW');
```

```shell
curl "https://hal9000/api/builds/b2.5KXaayW"
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/builds/b2.5KXaayW",
            "title": "b2.5KXaayW"
        },
        "user": {
            "href": "https://hal9000/api/users/3001",
            "title": "SKluck"
        },
        "application": {
            "href": "https://hal9000/api/applications/24",
            "title": "Hal Agent"
        },
        "environment": {
            "href": "https://hal9000/api/environments/1",
            "title": "test"
        },
        "logs": {
            "href": "https://hal9000/api/builds/b2.5KXaayW/logs"
        },
        "page": {
            "href": "https://hal9000/builds/b2.5KXaayW",
            "type": "text/html"
        },
        "github_reference_page": {
            "href": "http://git/hal/hal-agent/commit/3696a7a",
            "type": "text/html"
        },
        "github_commit_page": {
            "href": "http://git/hal/hal-agent/tree/master",
            "type": "text/html"
        },
        "start_push_page": {
            "href": "https://hal9000/builds/b2.5KXaayW/push",
            "type": "text/html"
        }
    },
    "id": "b2.5KXaayW",
    "status": "Success",
    "created": "2016-01-06T20:41:22Z",
    "start": "2016-01-06T20:41:35Z",
    "end": "2016-01-06T20:42:25Z",
    "reference": "master",
    "commit": "3696a7a5e59eb435f3f67e34e6b4d456092565e8"
}
```

Get a build.

### HTTP Request

`GET https://hal9000/api/builds/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build

## Get Event Logs for Build

```http
GET /api/builds/b2.5KXaayW/logs HTTP/1.1
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

$response = $client->get('/api/builds/b2.5KXaayW/logs');
```

```shell
curl "https://hal9000/api/builds/b2.5KXaayW/logs"
```

> ### Response

```json
{
    "_links": {
        "push": {
            "href": "https://hal9000/api/builds/b2.5KXaayW",
            "title": "b2.5KXaayW"
        },
        "logs": [
            {
                "href": "https://hal9000/api/eventlogs/e34a3e76d2a44ff7a3c7",
                "title": "e34a3e76d2a44ff7a3c7"
            },
            {
                "href": "https://hal9000/api/eventlogs/d3c75e80d95a4d4b8681",
                "title": "d3c75e80d95a4d4b8681"
            },
            {
                "href": "https://hal9000/api/eventlogs/93ac503d0b334da5a41f",
                "title": "93ac503d0b334da5a41f"
            },
            {
                "href": "https://hal9000/api/eventlogs/e87ce283a6514f4a8215",
                "title": "e87ce283a6514f4a8215"
            }
        ],
        "self": "https://hal9000/api/builds/b2.5KXaayW/logs"
    },
    "count": 4
}
```

Get Event Logs for a build. By default this returns a list of links to event logs, however the client can choose to
embed logs in the request.

<aside class="notice">
    Embedded logs do not include context data. If you require event logs data, access each event log resource individually.
</aside>

See [Event Logs](#event-logs) for more information about the **Event Log** resource.

### HTTP Request

`GET https://hal9000/api/builds/{id}/logs(?embed=logs)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build
embed     | **Optional** - Should the logs be embedded in this request?

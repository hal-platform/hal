# Builds

A build is a compiled application. To create a build, a user must choose an **environment**. Global builds (builds that
work in all environments) are not supported at this time, but likely will be in the future.

### Attributes

Attribute              | Description                                         | Type     | Example
---------------------- | --------------------------------------------------- | -------- | -------------
id                     | Unique build ID                                     | string   | `389c7f25-a6c9-4f19-8382-a8245674a7db`
status                 | Current status                                      | string   | `pending`, `running`, `success`, `failure`
created                | Time build was created in ISO 8601                  | string   | `2016-01-06T20:41:22Z`
start                  | Time build was started in ISO 8601                  | string   | `2016-01-06T20:42:00Z`
end                    | Time build was finished in ISO 8601                 | string   | `2016-01-06T20:45:36Z`
reference              | Git ref of VCS source                               | string   | `master`, `tag/1.0`, `pull/4`
commit                 | Commit SHA of VCS source                            | string   | `3696a7a5e59eb435f3f67e34e6b4d456092565e8`
user                   | **Link, Optional** - User that created build        | resource |
application            | **Link** - Application of build                     | resource |
environment            | **Link** - Environment built                        | resource |
events                 | **Link** - Events for build                         | list     |
page                   | **Link** - Page in frontend UI for this build       |          |
start_release_page     | **Link** - Page to initiate release of this build   |          |
github_reference_page  | **Link** - GitHub page for the git reference        |          |
github_commit_page     | **Link** - GitHub page for the exact commit SHA     |          |

## Get All Builds

```http
GET /api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds HTTP/1.1
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

$response = $client->get('/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds');
```

```shell
curl "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds"
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
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds"
        },
        "next": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds/page/2"
        },
        "last": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/builds/page/3"
        },
        "builds": [
            {
                "href": "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52",
                "title": "cf5717ad-527d-4649-843a-08ad01386a52"
            },
            {
                "href": "https://hal.computer/api/builds/b8c528c0-3476-430d-b0c1-f0c2b0c66592",
                "title": "b8c528c0-3476-430d-b0c1-f0c2b0c66592"
            },
            {
                "href": "https://hal.computer/api/builds/8abde05a-dde6-43dd-a2c4-87c7f581fdfc",
                "title": "8abde05a-dde6-43dd-a2c4-87c7f581fdfc"
            },
            {
                "href": "https://hal.computer/api/builds/caa8ccdb-6e0e-4857-b4f9-697e12ad071a",
                "title": "caa8ccdb-6e0e-4857-b4f9-697e12ad071a"
            },
            {
                "href": "https://hal.computer/api/builds/c5803620-665f-4897-8912-4cd988d91754",
                "title": "c5803620-665f-4897-8912-4cd988d91754"
            }
            //additional builds pruned for brevity
        ]
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

`GET https://hal.computer/api/applications/{id}/builds(/page/{page})`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application
page      | **Optional** - Page number to retrieve

## Get Build

```http
GET /api/builds/cf5717ad-527d-4649-843a-08ad01386a52 HTTP/1.1
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

$response = $client->get('/api/builds/cf5717ad-527d-4649-843a-08ad01386a52');
```

```shell
curl "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52"
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
            "href": "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52",
            "title": "cf5717ad-527d-4649-843a-08ad01386a52"
        },
        "user": {
            "href": "https://hal.computer/api/users/50290099-7b8a-471f-b9be-dbf7e9148349",
            "title": "SKluck"
        },
        "application": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc",
            "title": "Hal Agent"
        },
        "environment": {
            "href": "https://hal.computer/api/environments/a930555a-c330-435d-b720-1eb9d21b966f",
            "title": "test"
        },
        "events": {
            "href": "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52/events"
        },
        "page": {
            "href": "https://hal.computer/builds/cf5717ad-527d-4649-843a-08ad01386a52",
            "type": "text/html"
        },
        "github_reference_page": {
            "href": "https://github.com/hal-platform/hal-agent/commit/3696a7a",
            "type": "text/html"
        },
        "github_commit_page": {
            "href": "https://github.com/hal-platform/hal-agent/tree/master",
            "type": "text/html"
        },
        "start_release_page": {
            "href": "https://hal.computer/builds/cf5717ad-527d-4649-843a-08ad01386a52/release",
            "type": "text/html"
        }
    },
    "id": "cf5717ad-527d-4649-843a-08ad01386a52",
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

`GET https://hal.computer/api/builds/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build

## Get Events for Build

```http
GET /api/builds/cf5717ad-527d-4649-843a-08ad01386a52/events HTTP/1.1
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

$response = $client->get('/api/builds/cf5717ad-527d-4649-843a-08ad01386a52/events');
```

```shell
curl "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52/events"
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
            "href": "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52",
            "title": "cf5717ad-527d-4649-843a-08ad01386a52"
        },
        "events": [
            {
                "href": "https://hal.computer/api/job-events/38ad5416-6ee9-4407-8e5b-d2a198dc8d80",
                "title": "[0] Resolved build properties"
            },
            {
                "href": "https://hal.computer/api/job-events/0304ce63-1f81-4a51-a80b-867fb468f61b",
                "title": "[1] Reticulating splines"
            },
            {
                "href": "https://hal.computer/api/job-events/e7767e9a-0d23-4290-b268-e89e5d5c0b9f",
                "title": "[2] Prepare build environment"
            },
            {
                "href": "https://hal.computer/api/job-events/e8ef08f8-c35d-40c0-95eb-787fb1a647b1",
                "title": "[3] Archive build"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/builds/cf5717ad-527d-4649-843a-08ad01386a52/events"
        }
    },
    "count": 4
}
```

Get Events for a build. By default this returns a list of links to events, however the client can choose to
embed events in the request.

<aside class="notice">
    Embedded events do not include context data. If you require event data, access each event resource individually.
</aside>

See [Events](#events) for more information about the **Job Event** resource.

### HTTP Request

`GET https://hal.computer/api/builds/{id}/events(?embed=events)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build
embed     | **Optional** - Should the events be embedded in this request?


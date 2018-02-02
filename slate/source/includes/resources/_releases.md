# Releases

A release is a deployment to a target. Builds are already built per environment, so a release is created by selecting
a build, and a deployment target within that environment.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique release ID                                   | string   | `9e6400a0-2eaa-407a-b056-244f18f77997`
status          | Current status                                      | string   | `success`, `pending`, `running`
created         | Time release was created in ISO 8601                | string   | `2016-01-06T20:41:22Z`
start           | Time release was started in ISO 8601                | string   | `2016-01-06T20:42:00Z`
end             | Time release was finished in ISO 8601               | string   | `2016-01-06T20:45:36Z`
user            | **Link, Optional** - User that created release      | resource |
application     | **Link** - Application of build                     | resource |
target          | **Link** - Target deployed to                       | resource |
build           | **Link** - Build deployed                           | resource |
events          | **Link** - Events for release                       | list     |
environment     | **Link** - Environment released to                  | resource |
page            | **Link** - Page in frontend UI for this release     |          |

## Get All Releases

```http
GET /api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/releases HTTP/1.1
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

$response = $client->get('/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/releases');
```

```shell
curl "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/releases"
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
                "href": "https://hal.computer/api/releases/d623d547-9664-4e59-a427-3191c4ff1f07",
                "title": "d623d547-9664-4e59-a427-3191c4ff1f07"
            },
            {
                "href": "https://hal.computer/api/releases/9e0f3f54-cce1-4398-af6e-1bcdac313494",
                "title": "9e0f3f54-cce1-4398-af6e-1bcdac313494"
            },
            {
                "href": "https://hal.computer/api/releases/3a78b284-9687-45d0-8ce6-017ce00e852f",
                "title": "3a78b284-9687-45d0-8ce6-017ce00e852f"
            },
            {
                "href": "https://hal.computer/api/releases/fe100fda-84e1-4aed-b9a4-cd4b56791d41",
                "title": "fe100fda-84e1-4aed-b9a4-cd4b56791d41"
            },
            {
                "href": "https://hal.computer/api/releases/3cdce540-b489-4c34-9dd0-d6571029f232",
                "title": "3cdce540-b489-4c34-9dd0-d6571029f232"
            }
            //additional releases pruned for brevity
        ],
        "self": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/releases"
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
GET /api/releases/d80cb677-5302-44a0-a0d5-812c37367b19 HTTP/1.1
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

$response = $client->get('/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19');
```

```shell
curl "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19"
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
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19",
            "title": "d80cb677-5302-44a0-a0d5-812c37367b19"
        },
        "user": {
            "href": "https://hal.computer/api/users/50290099-7b8a-471f-b9be-dbf7e9148349",
            "title": "SKluck"
        },
        "target": {
            "href": "https://hal.computer/api/targets/bf55146d-328d-43ad-a572-6872100cabce",
            "title": "localhost"
        },
        "build": {
            "href": "https://hal.computer/api/builds/b1b88d6f-2b78-42cc-b7db-2fe99ba807fc",
            "title": "b1b88d6f-2b78-42cc-b7db-2fe99ba807fc"
        },
        "application": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc",
            "title": "Hal Agent"
        },
        "events": {
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events"
        },
        "environment": {
            "href": "https://hal.computer/api/environments/e7b3a5ee-8a2c-4d29-bc67-cb07368f841c",
            "title": "staging"
        },
        "page": {
            "href": "https://hal.computer/releases/d80cb677-5302-44a0-a0d5-812c37367b19",
            "type": "text/html"
        }
    },
    "id": "d80cb677-5302-44a0-a0d5-812c37367b19",
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
GET /api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events HTTP/1.1
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

$response = $client->get('/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events');
```

```shell
curl "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events"
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
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19",
            "title": "d80cb677-5302-44a0-a0d5-812c37367b19"
        },
        "events": [
            {
                "href": "https://hal.computer/api/job-events/91141fcf-36f6-4bad-9721-9ec3569b1916",
                "title": "[0] Resolved release properties"
            },
            {
                "href": "https://hal.computer/api/job-events/01300dc4-e6d6-4f56-aea6-2941fbc84dcf",
                "title": "[1] Copy archive to local storage"
            },
            {
                "href": "https://hal.computer/api/job-events/4a309b82-c0d2-4b92-970d-29e909bfd237",
                "title": "[2] Prepare release environment"
            },
            {
                "href": "https://hal.computer/api/job-events/837b0d05-de09-4bf3-ac19-2a1841584a82",
                "title": "[3] Code Deployment"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events"
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


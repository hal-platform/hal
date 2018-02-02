## Deploy Release

```http
POST /api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f/deploy HTTP/1.1
Accept: application/json
Host: hal.computer
Content-Type: application/json
Authorization: token "HAL_TOKEN"

{
    "targets": ["4f7e6c57-9887-4d05-a4b5-d429cd152e4d", "5235be5d-38e6-41de-aedd-f23be3b39c4e", "a080d0c6-f405-468d-b393-91b8be0ef934"]
}
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->post('/api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f/deploy', [
    'json' => [
        'targets' => ["4f7e6c57-9887-4d05-a4b5-d429cd152e4d"]
    ]
]);
```

```shell
curl \
  --request POST \
  --header "Authorization: token HAL_TOKEN" \
  --form targets[]="4f7e6c57-9887-4d05-a4b5-d429cd152e4d" \
  --form targets[]="5235be5d-38e6-41de-aedd-f23be3b39c4e" \
  "https://hal.computer/api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f/deploy"
```

> ### Response - Success

```http--response
HTTP/1.1 201 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": {
            "href": "https://hal.computer/api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f/deploy"
        }
    },
    "count": 2,
    "releases": [
        {
            "_links": {
                "self": {
                    "href": "https://hal.computer/api/releases/58629258-af32-43eb-91f0-80fd41319d3b",
                    "title": "58629258-af32-43eb-91f0-80fd41319d3b"
                },
                "user": {
                    "href": "https://hal.computer/api/users/75b384eb-926a-4586-b0a4-934fbf583b2f",
                    "title": "HAL_USER"
                },
                "target": {
                    "href": "https://hal.computer/api/targets/4f7e6c57-9887-4d05-a4b5-d429cd152e4d",
                    "title": "Test Target 1"
                },
                "build": {
                    "href": "https://hal.computer/api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f",
                    "title": "d4c5f758-930b-4099-a059-926ea81a9c3f"
                },
                "application": {
                    "href": "https://hal.computer/api/application/978f9bab-14a2-438b-b6b8-f922004d2a93",
                    "title": "Hal"
                },
                "events": {
                    "href": "https://hal.computer/api/releases/58629258-af32-43eb-91f0-80fd41319d3b/events"
                },
                "page": {
                    "href": "https://hal.computer/releases/58629258-af32-43eb-91f0-80fd41319d3b",
                    "type": "text/html"
                }
            },
            "id": "58629258-af32-43eb-91f0-80fd41319d3b",
            "status": "pending",
            "created": "2017-03-15T12:00:00Z",
            "start": null,
            "end": null
        },
        {
            "_links": {
                "self": {
                    "href": "https://hal.computer/api/releases/e0ff7da5-98fe-4523-a55a-7712788a7650",
                    "title": "e0ff7da5-98fe-4523-a55a-7712788a7650"
                },
                "user": {
                    "href": "https://hal.computer/api/users/75b384eb-926a-4586-b0a4-934fbf583b2f",
                    "title": "HAL_USER"
                },
                "target": {
                    "href": "https://hal.computer/api/targets/5235be5d-38e6-41de-aedd-f23be3b39c4e",
                    "title": "Test Target 2"
                },
                "build": {
                    "href": "https://hal.computer/api/builds/d4c5f758-930b-4099-a059-926ea81a9c3f",
                    "title": "d4c5f758-930b-4099-a059-926ea81a9c3f"
                },
                "application": {
                    "href": "https://hal.computer/api/application/978f9bab-14a2-438b-b6b8-f922004d2a93",
                    "title": "Hal"
                },
                "events": {
                    "href": "https://hal.computer/api/releases/e0ff7da5-98fe-4523-a55a-7712788a7650/events"
                },
                "page": {
                    "href": "https://hal.computer/releases/e0ff7da5-98fe-4523-a55a-7712788a7650",
                    "type": "text/html"
                }
            },
            "id": "e0ff7da5-98fe-4523-a55a-7712788a7650",
            "status": "pending",
            "created": "2017-03-15T12:00:00Z",
            "start": null,
            "end": null
        }
    ]
}
```

> ### Response - Client Error

```http--response
HTTP/1.1 400 Bad Request
Content-Type: application/problem+json
```

```json
{
    "status": 400,
    "title": "Bad Request",
    "detail": "Cannot deploy release due to form submission failure. Please check errors.",
    "errors": [
        "Push to "4f7e6c57-9887-4d05-a4b5-d429cd152e4d" cannot be created, deployment already in progress."
    ]
}
```

Deploy a release. Releases are created with the status of "pending". Releases are added the queue and processed
as soon as an agent is available.

Clients must authenticate to use this endpoint.

<aside class="warning">
    This endpoint is rate-limited. An application/environment pair can be built <b>20 times per minute</b>.
</aside>

### HTTP Request

`POST https://hal.computer/api/builds/{id}/deploy`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the build to deploy

<aside class="success">
    This endpoint accepts both JSON and form post media types.
    <ul>
        <li><code>application/json</code></li>
        <li><code>application/x-www-form-urlencoded</code></li>
        <li><code>multipart/form-data</code></li>
    </ul>
</aside>

### Request Fields

The following fields are submitted to deploy a release.

Field          | Description
-------------- | -----------
targets        | A list of IDs of deployment targets to deploy the release to.

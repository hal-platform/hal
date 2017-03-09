## Deploy Release

```http
GET /api/builds/b2.64U218P/deploy HTTP/1.1
Accept: application/json
Host: hal9000
Content-Type: application/json
Authorization: token "HAL_TOKEN"

{
    "targets": ["1234", "5678", "9999"]
}
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal9000',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->post('/api/builds/b2.64U218P/deploy', [
    'json' => [
        'targets' => ["1234"]
    ]
]);
```

```shell
curl \
  --request POST \
  --header "Authorization: token HAL_TOKEN" \
  --form targets[]=1234 \
  --form targets[]=5678 \
  "https://hal9000/api/builds/b2.64U218P/deploy"
```

> ### Response - Success

```
HTTP/1.1 201 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/builds/b2.64U218P/deploy"
        }
    },
    "count": 2,
    "releases": [
        {
            "_links": {
                "self": {
                    "href": "/api/pushes/p3.65ghrn4",
                    "title": "p3.65ghrn4"
                },
                "user": {
                    "href": "/api/users/5555",
                    "title": "HAL_USER"
                },
                "deployment": {
                    "href": "/api/targets/1234",
                    "title": "Test Target 1"
                },
                "build": {
                    "href": "/api/builds/b2.64U218P",
                    "title": "b2.64U218P"
                },
                "application": {
                    "href": "/api/application/999",
                    "title": "Hal"
                },
                "events": {
                    "href": "/api/pushes/p3.65ghrn4/events"
                },
                "page": {
                    "href": "/pushes/p3.65ghrn4",
                    "type": "text/html"
                }
            },
            "id": "p3.65ghrn4",
            "status": "Waiting",
            "created": "2017-03-15T12:00:00Z",
            "start": null,
            "end": null
        },
        {
            "_links": {
                "self": {
                    "href": "/api/pushes/p3.65gA633",
                    "title": "p3.65gA633"
                },
                "user": {
                    "href": "/api/users/5555",
                    "title": "HAL_USER"
                },
                "deployment": {
                    "href": "/api/targets/5678",
                    "title": "Test Target 2"
                },
                "build": {
                    "href": "/api/builds/b2.64U218P",
                    "title": "b2.64U218P"
                },
                "application": {
                    "href": "/api/application/999",
                    "title": "Hal"
                },
                "events": {
                    "href": "/api/pushes/p3.65gA633/events"
                },
                "page": {
                    "href": "/pushes/p3.65gA633",
                    "type": "text/html"
                }
            },
            "id": "p3.65gA633",
            "status": "Waiting",
            "created": "2017-03-15T12:00:00Z",
            "start": null,
            "end": null
        }
    ]
}
```

> ### Response - Client Error

```
HTTP/1.1 400 Bad Request
Content-Type: application/problem+json
```

```json
{
    "status": 400,
    "title": "Bad Request",
    "detail": "Cannot deploy release due to form submission failure. Please check errors.",
    "errors": [
        "Push to "1234" cannot be created, deployment already in progress."
    ]
}
```

Deploy a release. Releases are created with the status of "Pending" or "Waiting". Releases are added the queue and processed
as soon as an agent is available.

Clients must authenticate to use this endpoint.

<aside class="warning">
    This endpoint is rate-limited. An application/environment pair can be built <b>20 times per minute</b>.
</aside>

### HTTP Request

`POST https://hal9000/api/builds/{id}/deploy`

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

## Create Build

```http
GET /api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/build HTTP/1.1
Accept: application/json
Host: hal.computer
Content-Type: application/json
Authorization: token "HAL_TOKEN"

{
    "environment": "1",
    "reference": "master",
    "targets": ["502", "503"]
}
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal.computer',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->post('/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/build', [
    'json' => [
        'environment' => 'test',
        'reference' => 'pull/45'
    ]
]);
```

```shell
curl \
  --request POST \
  --header "Authorization: token HAL_TOKEN" \
  --form environment=test \
  --form reference=master \
  "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/build"
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
            "href": "https://hal.computer/api/builds/9c27e9e1-90c9-4f8c-81c3-79febdea08e1",
            "title": "9c27e9e1-90c9-4f8c-81c3-79febdea08e1"
        },
        "user": {
            "href": "https://hal.computer/api/users/50290099-7b8a-471f-b9be-dbf7e9148349",
            "title": "SKluck"
        },
        "application": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe",
            "title": "HAL Agent"
        },
        "environment": {
            "href": "https://hal.computer/api/environments/a930555a-c330-435d-b720-1eb9d21b966f",
            "title": "test"
        },
        "events": {
            "href": "https://hal.computer/api/builds/9c27e9e1-90c9-4f8c-81c3-79febdea08e1/events"
        },
        "page": {
            "href": "https://hal.computer/builds/9c27e9e1-90c9-4f8c-81c3-79febdea08e1",
            "type": "text/html"
        },
        "github_reference_page": {
            "href": "https://github.com/hal-platform/hal-agent/commit/9075f4b",
            "type": "text/html"
        },
        "github_commit_page": {
            "href": "https://github.com/hal-platform/hal-agent/tree/master",
            "type": "text/html"
        }
    },
    "id": "9c27e9e1-90c9-4f8c-81c3-79febdea08e1",
    "status": "Waiting",
    "created": "2016-01-20T18:09:46Z",
    "start": null,
    "end": null,
    "reference": "master",
    "commit": "9075f4bee1ae34023d2f95b6670f02dd301b4a8b"
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
    "detail": "Cannot start build due to form submission failure. Please check errors.",
    "errors": {
        "reference": [
            "You must select a valid git reference."
        ]
    }
}
```

Create a build. Builds are created with the status of "Pending" or "Waiting". Builds are added the queue and processed
as soon as an agent is available.

Clients must authenticate to use this endpoint.

<aside class="warning">
    This endpoint is rate-limited. An application/environment can be built <b>10 times per minute</b>.
</aside>

### HTTP Request

`POST https://hal.computer/api/applications/{id}/build`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application

<aside class="success">
    This endpoint accepts both JSON and form post media types.
    <ul>
        <li><code>application/json</code></li>
        <li><code>application/x-www-form-urlencoded</code></li>
        <li><code>multipart/form-data</code></li>
    </ul>
</aside>

### Request Fields

The following fields are submitted to create a build.

Field          | Description
-------------- | -----------
reference      | The reference from the VCS repository to the code snapshot to build.
environment    | **Optional** - The ID or name of the environment to build. Exclude this parameter to create a build that can be used in all environments.
targets        | **Optional** - A list of IDs of deployment targets to automatically deploy if build is successful.

<aside class="notice">
    Hal understands the following formats for <b>reference</b>.
    <ul>
        <li><b>Pull Requests</b> - Prefixed with <code>pull/</code> (<code>pull/3</code>, <code>pull/523</code>)</li>
        <li><b>Releases</b> - Prefixed with <code>tag/</code> (<code>tag/2.0</code>, <code>tag/1.0.0-alpha1</code>)</li>
        <li><b>Commits</b> - 40-character hash (<code>3696a7a5e59eb435f3f67e34e6b4d456092565e8</code>)</li>
        <li><b>Branches</b> - Any value that does not conform to the above templates (<code>master</code>, <code>my-test-branch</code>)</li>
    </ul>
</aside>

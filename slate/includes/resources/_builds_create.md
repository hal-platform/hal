## Create Build

```http
GET /api/applications/24/build HTTP/1.1
Accept: application/json
Host: hal9000
Content-Type: application/json
Authorization: token "HAL_TOKEN"

{
    "environment": "1",
    "reference": "master",
    "deployments": [502, 503]
}
```

``` http
HTTP/1.1 201 OK
Content-Type: application/hal+json
```

```php
<?php
$client = new Client([
    'base_uri' => 'https://hal9000',
    'headers' => ['Authorization' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->post('/api/applications/24/build', [
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
  "https://hal9000/api/applications/24/build"
```

> ### Response - Success

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/builds/b2.5LcovUf",
            "title": "b2.5LcovUf"
        },
        "user": {
            "href": "https://hal9000/api/users/3001",
            "title": "SKluck"
        },
        "application": {
            "href": "https://hal9000/api/applications/24",
            "title": "HAL 9000 Agent"
        },
        "environment": {
            "href": "https://hal9000/api/environments/1",
            "title": "test"
        },
        "logs": {
            "href": "https://hal9000/api/builds/b2.5LcovUf/logs"
        },
        "page": {
            "href": "https://hal9000/builds/b2.5LcovUf",
            "type": "text/html"
        },
        "github_reference_page": {
            "href": "http://git/hal/hal-agent/commit/9075f4b",
            "type": "text/html"
        },
        "github_commit_page": {
            "href": "http://git/hal/hal-agent/tree/master",
            "type": "text/html"
        }
    },
    "id": "b2.5LcovUf",
    "status": "Waiting",
    "created": "2016-01-20T18:09:46Z",
    "start": null,
    "end": null,
    "reference": "master",
    "commit": "9075f4bee1ae34023d2f95b6670f02dd301b4a8b"
}
```

> ### Response - Client Error

```json
{
    "status": 400,
    "title": "Bad Request",
    "detail": "Cannot start build due to form submission failure. Please check errors.",
    "errors": [
        "Environment is required."
    ]
}
```

Create a build. Builds are created with the status of "Pending" or "waiting". Builds are added the queue and processed
as soon as an agent is available.

Clients must authenticate to use this endpoint.

<aside class="warning">
    This endpoint is currently rate-limited. An application/environment pair can only be built every <b>10 seconds</b>.
</aside>

### HTTP Request

`POST https://hal9000/api/applications/{id}/build`

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
environment    | The ID or name of the environment to build
reference      | The reference from the VCS repository to the code snapshot to build.
deployments    | **Optional** - A list of IDs of deployments to automatically push if build is successful.

<aside class="notice">
    Hal understands the following formats for <b>reference</b>.
    <ul>
        <li><b>Pull Requests</b> - Prefixed with <code>pull/</code> (<code>pull/3</code>, <code>pull/523</code>)</li>
        <li><b>Releases</b> - Prefixed with <code>tag/</code> (<code>tag/2.0</code>, <code>tag/1.0.0-alpha1</code>)</li>
        <li><b>Commits</b> - 40-character hash (<code>3696a7a5e59eb435f3f67e34e6b4d456092565e8</code>)</li>
        <li><b>Branches</b> - Any value that does not conform to the above templates (<code>master</code>, <code>my-test-branch</code>)</li>
    </ul>
</aside>
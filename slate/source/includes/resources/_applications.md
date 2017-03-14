# Applications

An application is a isolated deployable service or application. Each application is mapped to
a single VCS repository. These are not unique. The same source repository can be used
for multiple applications.

### Attributes

Attribute       | Description                                                   | Type     | Example
--------------- | ------------------------------------------------------------- | -------- | -------------
id              | Unique application ID                                         | number   | `53`
key             | Unique alphanumeric identifier for this application           | string   | `hal-agent`, `testapp`
name            | Application name                                              | string   | `HAL Agent`, `Test Application`
organization    | **Link** - Organization this application belongs to           | resource |
targets         | **Link** - List of targets for this application               | list     |
builds          | **Link** - List of builds for this application                | list     |
pushes          | **Link** - List of pushes for this application                | list     |
page            | **Link** - Page in frontend UI for this application           |
status_page     | **Link** - Status page for this application                   |
github_page     | **Link** - GitHub page for the repository of this application |


## Get All Applications

```http
GET /api/applications HTTP/1.1
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

$response = $client->get('/api/applications');
```

```shell
curl "https://hal.computer/api/applications"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "applications": [
            {
                "href": "https://hal.computer/api/applications/1",
                "title": "Hal"
            },
            {
                "href": "https://hal.computer/api/applications/24",
                "title": "Hal Agent"
            },
            {
                "href": "https://hal.computer/api/applications/823",
                "title": "test application 1"
            },
            {
                "href": "https://hal.computer/api/applications/9000",
                "title": "Secret Application"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/applications"
        }
    },
    "count": 4,
    "total": 4,
    "page": 1
}
```

Get all applications.

### HTTP Request

`GET https://hal.computer/api/applications`

## Get Application

```http
GET /api/applications/24 HTTP/1.1
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

$response = $client->get('/api/applications/24');
```

```shell
curl "https://hal.computer/api/applications/24"
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
            "href": "https://hal.computer/api/applications/24",
            "title": "Hal Agent"
        },
        "organization": {
            "href": "https://hal.computer/api/organizations/5",
            "title": "testing"
        },
        "targets": {
            "href": "https://hal.computer/api/applications/24/targets"
        },
        "builds": {
            "href": "https://hal.computer/api/applications/24/builds"
        },
        "pushes": {
          "href": "https://hal.computer/api/applications/24/pushes"
        },
        "page": {
            "href": "https://hal.computer/applications/24",
            "title": "Hal Agent",
            "type": "text/html"
        },
        "status_page": {
            "href": "https://hal.computer/applications/24/status",
            "title": "Hal Agent Status",
            "type": "text/html"
        },
        "github_page": {
            "href": "https://github.com/hal-platform/hal-agent",
            "type": "text/html"
        }
    },
      "id": 24,
      "key": "hal-agent",
      "name": "Hal Agent"
}
```

This endpoint retrieves a specific application.

### HTTP Request

`GET https://hal.computer/api/application/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application

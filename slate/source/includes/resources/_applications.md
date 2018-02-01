# Applications

An application is a isolated deployable service or application. Each application is mapped to
a single VCS repository. These are not unique. The same source repository can be used
for multiple applications.

### Attributes

Attribute       | Description                                                     | Type     | Example
--------------- | --------------------------------------------------------------- | -------- | -------------
id              | Unique application ID                                           | number   | `e471d587-8ae6-4fd8-99de-ae6b584c55`
key             | Unique alphanumeric identifier for this application             | string   | `hal-agent`, `testapp`
name            | Application name                                                | string   | `HAL Agent`, `Test Application`
organization    | **Link** - Organization this application belongs to             | resource |
targets         | **Link** - List of targets for this application                 | list     |
builds          | **Link** - List of builds for this application                  | list     |
pushes          | **Link** - List of pushes for this application                  | list     |
page            | **Link** - Page in frontend UI for this application             |
status_page     | **Link** - Status page for this application                     |
github_page     | **Link** - GitHub page for the repository of this application   |
vcs_provider    | **Link** - Version Control System Provider for this application |


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
                "href": "https://hal.computer/api/applications/7a1cbe4b-3158-413f-a3fa-556e9d35fba2",
                "title": "Hal"
            },
            {
                "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe",
                "title": "Hal Agent"
            },
            {
                "href": "https://hal.computer/api/applications/c2f8003f-71df-474e-8c82-d00a851ead15",
                "title": "test application 1"
            },
            {
                "href": "https://hal.computer/api/applications/00000000-0000-0000-0000-000000000000",
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
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe",
            "title": "Hal Agent"
        },
        "organization": {
            "href": "https://hal.computer/api/organizations/79a13cea-9163-434d-ab99-77de3d86244c",
            "title": "testing"
        },
        "targets": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/targets"
        },
        "builds": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/builds"
        },
        "pushes": {
          "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/pushes"
        },
        "page": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe",
            "title": "Hal Agent",
            "type": "text/html"
        },
        "status_page": {
            "href": "https://hal.computer/api/applications/1f68d71a-5de4-4f61-b657-eebb090ba8fe/status",
            "title": "Hal Agent Status",
            "type": "text/html"
        },
        "github_page": {
            "href": "https://github.com/hal-platform/hal-agent",
            "type": "text/html"
        },
        "vcs_provider": {
            "href": "https://hal.computer/api/vcs-providers/048577b9-2fe2-4779-bfe2-c2fb1e74ae78",
            "title": "Github"
        }
    },
      "id": "1f68d71a-5de4-4f61-b657-eebb090ba8fe",
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

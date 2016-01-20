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
email           | Email address used for build and push notifications           | string   | `notifications@example.com`
group           | **Link** - Group this application belongs to                  | resource |
deployments     | **Link** - List of deployments for this application           | list     |
builds          | **Link** - List of builds for this application                | list     |
pushes          | **Link** - List of pushes for this application                | list     |
page            | **Link** - Page in frontend UI for this application           |
status_page     | **Link** - Status page for this application                   |
github_page     | **Link** - GitHub page for the repository of this application |

## Application Groups

A group is merely an organizational unit for applications. Permissions cannot be granted to entire groups, and the group
an application belongs to has no bearing on who can deploy an application.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique group ID                                     | number   | `5`
key             | Unique alphanumeric identifier for this group       | string   | `testing`, `example-group`
name            | Group name                                          | string   | `Test Group`


## Get All Applications

```http
GET /api/applications HTTP/1.1
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

$response = $client->get('/api/applications');
```

```shell
curl "https://hal9000/api/applications"
```

> ### Response

```json
{
    "_links": {
        "applications": [
            {
                "href": "https://hal9000/api/applications/1",
                "title": "Hal"
            },
            {
                "href": "https://hal9000/api/applications/24",
                "title": "Hal Agent"
            },
            {
                "href": "https://hal9000/api/applications/823",
                "title": "test application 1"
            },
            {
                "href": "https://hal9000/api/applications/9000",
                "title": "Secret Application"
            }
        ],
        "self": "https://hal9000/api/applications"
    },
    "count": 4
}
```

Get all applications.

### HTTP Request

`GET https://hal9000/api/applications`

## Get Application

```http
GET /api/applications/24 HTTP/1.1
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

$response = $client->get('/api/applications/24');
```

```shell
curl "https://hal9000/api/applications/24"
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/applications/24",
            "title": "Hal Agent"
        },
        "group": {
            "href": "https://hal9000/api/groups/5",
            "title": "testing"
        },
        "deployments": {
            "href": "https://hal9000/api/applications/24/deployments"
        },
        "builds": {
            "href": "https://hal9000/api/applications/24/builds"
        },
        "pushes": {
          "href": "https://hal9000/api/applications/24/pushes"
        },
        "page": {
            "href": "https://hal9000/applications/24",
            "title": "Hal Agent",
            "type": "text/html"
        },
        "status_page": {
            "href": "https://hal9000/applications/24/status",
            "title": "Hal Agent Status",
            "type": "text/html"
        },
        "github_page": {
            "href": "http://git/web-core/hal-agent",
            "type": "text/html"
        }
    },
      "id": 24,
      "key": "hal-agent",
      "name": "Hal Agent",
      "email": "test@example.com"
}
```

This endpoint retrieves a specific application.

### HTTP Request

`GET https://hal9000/api/application/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the application

## Organizations

An organization is merely an organizational unit for applications. Permissions
cannot be granted to entire organizations, and the organizations an application
belongs to has no bearing on who can deploy an application.

### Attributes

Attribute       | Description                                          | Type     | Example
--------------- | ---------------------------------------------------- | -------- | -------------
id              | Unique organization ID                               | number   | `5`
key             | Unique alphanumeric identifier for this organization | string   | `testing`, `example-organization`
name            | Organization name                                    | string   | `Test Organization`

## Get All Organizations

```http
GET /api/organizations HTTP/1.1
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

$response = $client->get('/api/organizations');
```

```shell
curl "https://hal.computer/api/organizations"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "organizations": [
            {
                "href": "https://hal.computer/api/organizations/12",
                "title": "sample-org"
            },
            {
                "href": "https://hal.computer/api/organizations/360",
                "title": "test-org"
            },
            {
                "href": "https://hal.computer/api/organizations/5",
                "title": "testing"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/organizations"
        }
    },
    "count": 3
}
```

Get all organizations.

### HTTP Request

`GET https://hal.computer/api/organizations`

## Get Organization

```http--response
GET /api/organizations/5 HTTP/1.1
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

$response = $client->get('/api/organizations/5');
```

```shell
curl "https://hal.computer/api/organizations/5"
```

> ### Response

```http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "self": {
            "href": "https://hal.computer/api/organizations/5",
            "title": "testing"
        }
    },
    "id": 1,
    "key": "testing",
    "name": "Example Application Organization"
}
```

Get a specific organization.

### HTTP Request

`GET https://hal.computer/api/organizations/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the organization

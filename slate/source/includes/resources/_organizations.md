## Organizations

An organization is merely an organizational unit for applications. Permissions
cannot be granted to entire organizations, and the organizations an application
belongs to has no bearing on who can deploy an application.

### Attributes

Attribute       | Description                                          | Type     | Example
--------------- | ---------------------------------------------------- | -------- | -------------
id              | Unique organization ID                               | number   | `f115a0a4-c5bb-41f9-85fc-1f30b82d7f15`
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
                "href": "https://hal.computer/api/organizations/44620f93-786a-46d8-a983-3e5074d85604",
                "title": "Sample Organization"
            },
            {
                "href": "https://hal.computer/api/organizations/a85fbeee-dc8f-4b14-a4cb-9df1b833cb84",
                "title": "Test Organization"
            },
            {
                "href": "https://hal.computer/api/organizations/42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441",
                "title": "Example Application Organization"
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
GET /api/organizations/42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441 HTTP/1.1
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

$response = $client->get('/api/organizations/42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441');
```

```shell
curl "https://hal.computer/api/organizations/42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441"
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
            "href": "https://hal.computer/api/organizations/42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441",
            "title": "testing"
        }
    },
    "id": "42eeaa4f-6ba9-4dc6-a2c7-2bd76482f441",
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

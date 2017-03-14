# Environments

An environment where applications are deployed to. There is no hierarchy for environments.
Permissions for environments designated as "production" are handled differently, as
these environments are usually more sensitive.

### Attributes

Attribute       | Description                | Type     | Example
--------------- | -------------------------- | -------- | -------------
id              | Unique environment ID      | number   | `42`
name            | Unique environment name    | string   | `test`, `prod`
is_production   | Is environment production? | bool     | `true`

## Get All Environments

```http
GET /api/environments HTTP/1.1
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

$response = $client->get('/api/environments');
```

```shell
curl "https://hal.computer/api/environments"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "environments": [
            {
                "href": "https://hal.computer/api/environments/1",
                "title": "dev"
            },
            {
                "href": "https://hal.computer/api/environments/2",
                "title": "staging"
            },
            {
                "href": "https://hal.computer/api/environments/3",
                "title": "prod"
            },
            {
                "href": "https://hal.computer/api/environments/4",
                "title": "security"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/environments"
        }
    },
    "count": 4
}
```

This endpoint retrieves all environments.

### HTTP Request

`GET https://hal.computer/api/environments`

## Get Environment

```http
GET /api/environments/4 HTTP/1.1
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

$response = $client->get('/api/environments/4');
```

```shell
curl "https://hal.computer/api/environments/4"
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
            "href": "https://hal.computer/api/environments/1",
            "title": "test"
        }
    },
    "id": 1,
    "name": "test",
    "is_production": false
}
```

This endpoint retrieves a specific environment.

### HTTP Request

`GET https://hal.computer/environments/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the environment

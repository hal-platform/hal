# Environments

An environment where applications are deployed to. There is no hierarchy for environments.
Permissions for environments designated as "production" are handled differently, as
these environments are usually more sensitive.

### Attributes

Attribute       | Description                | Type     | Example
--------------- | -------------------------- | -------- | -------------
id              | Unique environment ID      | number   | `bb1faf2c-6710-4233-9fee-6deec56cfa67`
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
                "href": "https://hal.computer/api/environments/bb1faf2c-6710-4233-9fee-6deec56cfa67",
                "title": "dev"
            },
            {
                "href": "https://hal.computer/api/environments/e7b3a5ee-8a2c-4d29-bc67-cb07368f841c",
                "title": "staging"
            },
            {
                "href": "https://hal.computer/api/environments/3541ffc8-8596-40d2-b0e5-4ec56f62335e",
                "title": "prod"
            },
            {
                "href": "https://hal.computer/api/environments/9eadf152-1ae6-44d9-b07c-6a1b3d9df027",
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
GET /api/environments/9eadf152-1ae6-44d9-b07c-6a1b3d9df027 HTTP/1.1
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

$response = $client->get('/api/environments/9eadf152-1ae6-44d9-b07c-6a1b3d9df027');
```

```shell
curl "https://hal.computer/api/environments/9eadf152-1ae6-44d9-b07c-6a1b3d9df027"
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
            "href": "https://hal.computer/api/environments/9eadf152-1ae6-44d9-b07c-6a1b3d9df027",
            "title": "security"
        }
    },
    "id": "9eadf152-1ae6-44d9-b07c-6a1b3d9df027",
    "name": "security",
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

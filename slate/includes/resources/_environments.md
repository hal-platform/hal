# Environments

An environment where applications are deployed to. There is no hierarchy for environments.
Permissions for environments designated as "production" are handled differently, as
these environments are usually more sensitive.

### Attributes

Attribute       | Description                | Type     | Example
--------------- | -------------------------- | -------- | -------------
id              | Unique environment ID      | number   | `42`
name            | Unique environment name    | string   | `test`, `prod`
isProduction    | Is environment production? | bool     | `true`

## Get All Environments

```http
GET /api/environments HTTP/1.1
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
    'headers' => ['Authentication' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api/environments');
```

```shell
curl "https://hal9000/api/environments"
```

> ### Response

```json
{
  "_links": {
    "environments": [
      {
        "href": "https://hal9000/api/environments/1",
        "title": "test"
      },
      {
        "href": "https://hal9000/api/environments/2",
        "title": "beta"
      },
      {
        "href": "https://hal9000/api/environments/3",
        "title": "prod"
      },
      {
        "href": "https://hal9000/api/environments/4",
        "title": "security"
      }
    ],
    "self": "https://hal9000/api/environments"
  },
  "count": 4
}
```

This endpoint retrieves all environments.

### HTTP Request

`GET https://hal9000/api/environments`

## Get Environment

```http
GET /api/environments/4 HTTP/1.1
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
    'headers' => ['Authentication' => sprintf('token %s', getenv('HAL_TOKEN'))]
]);

$response = $client->get('/api/environments/4');
```

```shell
curl "https://hal9000/api/environments/4"
```

> ### Response

```json
{
  "_links": {
    "self": {
      "href": "https://localhost.hal/api/environments/1",
      "title": "test"
    }
  },
  "id": 1,
  "name": "test",
  "isProduction": false
}
```

This endpoint retrieves a specific environment.

### HTTP Request

`GET https://hal9000/environments/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the environment

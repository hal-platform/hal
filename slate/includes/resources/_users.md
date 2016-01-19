# Users

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique user ID                                      | number   | `2001`
handle          | Username or handle                                  | string   | `FPoole2001`
name            | Full name                                           | string   | `Dr. Frank Poole`
email           | Email address                                       | string   | `frank@discoveryone.spaceships.nasa.gov`
isActive        | Is the user account active?                         | bool     | `true`
permissions     | Current users permission levels                     | object   |

## Get All Users

```http
GET /api/users HTTP/1.1
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

$response = $client->get('/api/users');
```

```shell
curl "https://hal9000/api/users"
```

> ### Response

```json
{
    "_links": {
        "users": [
          {
            "href": "https://hal9000/api/users/1001",
            "title": "DBowman"
          },
          {
            "href": "https://hal9000/api/users/2001",
            "title": "FPoole"
          },
          {
            "href": "https://hal9000/api/users/3001",
            "title": "SKluck"
          },
          {
            "href": "https://hal9000/api/users/4001",
            "title": "JSchmoe"
          }
        ],
        "self": "https://hal9000/api/users"
    },
    "count": 4,
    "total": 4,
    "page": 1
}
```

This endpoint retrieves all users.

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 users</b>.
</aside>

### HTTP Request

`GET https://hal9000/api/users?page={page}`

### URL Parameters

Parameter   | Description
----------- | -----------
page        | **Optional** - Page number to retrieve

## Get User

```http
GET /api/users/3001 HTTP/1.1
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

$response = $client->get('/api/users/3001');
```

```shell
curl "https://hal9000/api/users/3001"
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://localhost.hal/api/users/3001",
            "title": "SKluck"
        }
    },
    "id": 3001,
    "handle": "SKluck",
    "name": "Kluck, Steve",
    "email": "SteveKluck@quickenloans.com",
    "isActive": true,
    "permissions": {
        "standard": false,
        "lead": false,
        "admin": false,
        "super": true
    }
}
```

This endpoint retrieves a specific user.

### HTTP Request

`GET https://hal9000/api/users/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the user

# Templates

A **template** is an incomplete blueprint for deployment targets.

### Attributes

Attribute       | Description                                                     | Type     | Example
--------------- | --------------------------------------------------------------- | -------- | -------------
id              | Unique template ID                                              | number   | `9234c624-fc71-42bc-b001-6ed89e0ca295`
name            | Name for this template                                          | string   | `localhost`, `us-east-1`
type            | Deployment type                                                 | string   | `rsync`, `cd`, `eb`, `s3`, `script`
application     | **Optional, Link** - Application for this target                | resource |
environment     | **Optional, Embedded** - Environment this template belongs to   | resource |
targets         | **Link** - List of targets using this template                  | list     |
parameters      | Configuration properties                                        | object   |

The `parameter` attribute aligns with the configuration properties of **Target** resources. see [Targets](#targets) for more information.

## Get All Templates

```http
GET /api/templates HTTP/1.1
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

$response = $client->get('/api/templates');
```

```shell
curl "https://hal.computer/api/templates"
```

> ### Response

```http--response
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
  "_links": {
    "next": {
        "href": "https://hal.computer/api/templates/page/2"
    },
    "last": {
        "href": "https://hal.computer/api/templates/page/2"
    },
    "templates": [
      {
        "href": "https://hal.computer/api/templates/59588f43-9e59-44e5-a1bc-db3d5cd37968",
        "title": "qltesttemplate"
      },
      {
        "href": "https://hal.computer/api/templates/f4323b61-c340-4f57-9fd1-eb697fd38f38",
        "title": "localhost"
      },
      {
        "href": "https://hal.computer/api/templates/0642335e-02d3-468c-b7c7-f9a69f9a1252",
        "title": "test.example.com"
      },
      {
        "href": "https://hal.computer/api/templates/f72ebc2d-09a1-45ec-9946-30943298978b",
        "title": "S3 (us-east-1)"
      },
      {
        "href": "https://hal.computer/api/templates/ac14f318-9541-40f7-b83b-41d73a1b14cc",
        "title": "CD (us-east-1)"
      }
    ],
    "self": {
        "href": "https://hal.computer/api/templates"
    }
  },
  "count": 5,
  "total": 6,
  "page": 1
}
```

This endpoint retrieves all templates.

<aside class="notice">
    This endpoint is <b>paged</b>. The maximum size of each page is <b>25 templates</b>.
</aside>

### HTTP Request

`GET https://hal.computer/api/templates(/page/{page})`

### URL Parameters

Parameter   | Description
----------- | -----------
page        | **Optional** - Page number to retrieve

## Get Template

```http
GET /api/templates/f72ebc2d-09a1-45ec-9946-30943298978b HTTP/1.1
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

$response = $client->get('/api/templates/f72ebc2d-09a1-45ec-9946-30943298978b');
```

```shell
curl "https://hal.computer/api/templates/f72ebc2d-09a1-45ec-9946-30943298978b"
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
            "href": "https://hal.computer/api/templates/f72ebc2d-09a1-45ec-9946-30943298978b",
            "title": "S3 (us-east-1)"
        },
        "targets": [
            {
                "href": "https://hal.computer/api/targets/5ae58f52-1232-49b4-adbc-a8991b1e14b2",
                "title": "S3 (bucket-name)"
            }
        ]
    },
    "_embedded": {
        "environment": {
            "_links": {
                "self": {
                    "href": "https://hal.computer/api/environments/3acdee22-ee6f-45ce-846c-986fe8a37330",
                    "title": "staging"
                }
            },
            "id": "3acdee22-ee6f-45ce-846c-986fe8a37330",
            "name": "beta",
            "is_production": false
        }
    },
    "id": "f72ebc2d-09a1-45ec-9946-30943298978b",
    "type": "s3",
    "name": "us-east-1"
}
```

This endpoint retrieves a specific template.

### HTTP Request

`GET https://hal.computer/templates/{id}`

### URL Parameters

Parameter   | Description
----------- | -----------
id          | The unique ID of the template

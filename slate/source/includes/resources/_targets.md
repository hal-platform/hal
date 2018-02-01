# Targets

A deployment target in the **Hal domain model** is not an action, but a resource.

### Attributes

Attribute            | Description                                                  | Type        | Example
-------------------- | ------------------------------------------------------------ | ----------- | -------------
id                   | Unique group ID                                              | number      | `0721219c-f48b-4efb-9204-18ce484c9246`
name                 | **Optional** - Name given to this target                     | string,null | `test-target`
url                  | **Optional** - Full URL to access application                | string      | `http://group1.example.com`
type                 | Deployment type                                              | string      | `rsync`, `cd`, `eb`, `s3`, `script`
parameters           | Configuration properties                                     | object      |
template             | **Optional, Embedded** - Template this target is based off   | resource    |
application          | **Link** - Application for this target                       | resource    |
environment          | **Link** - Environment this target belongs to                | resource    |
releases             | **Link** - List of releases to this target                   | list        |
current_release      | **Link** - Last deployed release                             | list        |

The following configuration properties are located under the `parameter` attribute.

#### RSync Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
path                 | Fully qualified file path                        | string,null | `/example/path`

#### AWS CodeDeploy Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
application          | Application name                                 | string,null | `testapp`
group                | Deployment group                                 | string,null | `testapp-deploy1`
configuration        | Deployment configuration                         | string,null | `CodeDeployDefault.AllAtOnce`
bucket               | Bucket name Configuration                        | string,null | `test-bucket`
source               | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`
path                 | File name (may include directories)              | string,null | `test/path`


#### AWS Elastic Beanstalk Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
application          | Application name                                 | string,null | `DemoApplication`
environment          | Environment name                                 | string,null | `e-mvbatnpyzv`
bucket               | Bucket name Configuration                        | string,null | `test-bucket`
source               | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`
path                 | File name (may include directories)              | string,null | `test/path`

#### AWS S3 Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
sbucket              | Bucket name Configuration                        | string,null | `test-bucket`
s3_method            | S3 deployment method for this target             | string      | `sync`, `artifact`
source               | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`
path                 | File name (may include directories)              | string,null | `test/path`

#### Script Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
context              | Context data passed to deploy scripts            | string,null | `group-pool1`

## Get All Targets

```http
GET /api/applications/58483556-0f73-4c97-af24-954cce3a73cc/targets HTTP/1.1
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

$response = $client->get('/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/targets');
```

```shell
curl "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/targets"
```

> ### Response

```http
HTTP/1.1 200 OK
Content-Type: application/hal+json
```

```json
{
    "_links": {
        "targets": [
            {
                "href": "https://hal.computer/api/targets/f4958442-f586-4c7f-8df9-ded35c13863a",
                "title": "EB (e-mvbatnpyzv)"
            },
            {
                "href": "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813",
                "title": "S3 (bucket-name)"
            },
            {
                "href": "https://hal.computer/api/targets/05490c76-b699-4689-a547-e370544b083f",
                "title": "localhost"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc/targets"
        }
    },
    "count": 3
}
```

Get all targets for a specific application.

### HTTP Request

`GET https://hal.computer/api/applications/{id}/targets`

## Get Target

```http
GET /api/targets/37e35979-482a-40da-b7b8-af6e3230a813 HTTP/1.1
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

$response = $client->get('/api/targets/37e35979-482a-40da-b7b8-af6e3230a813');
```

```shell
curl "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813
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
            "href": "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813",
            "title": "S3 (bucket-name)"
        },
        "application": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc",
            "title": "Hal Agent"
        },
        "releases": {
            "href": "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813/releases"
        },
        "current_release": {
            "href": "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813/current-release"
        }
    },
    "_embedded": {
        "template": {
            "id": "28d228b2-36f7-4cc7-b994-8a9f070fc644"
            //template
        }
    },
    "id": "37e35979-482a-40da-b7b8-af6e3230a813",
    "name": "",
    "url": "http://example.com",
    "configuration": {
        "path": null,
        "application": null,
        "group": null,
        "configuration": null,
        "environment": null,
        "s3_method": "sync",
        "bucket": "bucket-name",
        "source": "testapp/$PUSHID.tar.gz",
        "path": "test/path"
    }
}
```

Get details for a specific targets.

### HTTP Request

`GET https://hal.computer/api/targets/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the target

## Get currently deployed Release to Target

```http
GET /api/targets/37e35979-482a-40da-b7b8-af6e3230a813/current-release HTTP/1.1
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

$response = $client->get('/api/targets/37e35979-482a-40da-b7b8-af6e3230a813/current-release');
```

```shell
curl "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813/current-release
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
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19",
            "title": "d80cb677-5302-44a0-a0d5-812c37367b19"
        },
        "user": {
            "href": "https://hal.computer/api/users/50290099-7b8a-471f-b9be-dbf7e9148349",
            "title": "SKluck"
        },
        "target": {
            "href": "https://hal.computer/api/targets/37e35979-482a-40da-b7b8-af6e3230a813",
            "title": "localhost"
        },
        "build": {
            "href": "https://hal.computer/api/builds/b1b88d6f-2b78-42cc-b7db-2fe99ba807fc",
            "title": "b1b88d6f-2b78-42cc-b7db-2fe99ba807fc"
        },
        "application": {
            "href": "https://hal.computer/api/applications/58483556-0f73-4c97-af24-954cce3a73cc",
            "title": "Hal Agent"
        },
        "events": {
            "href": "https://hal.computer/api/releases/d80cb677-5302-44a0-a0d5-812c37367b19/events"
        },
        "page": {
            "href": "https://hal.computer/releases/d80cb677-5302-44a0-a0d5-812c37367b19",
            "type": "text/html"
        }
    },
    "id": "d80cb677-5302-44a0-a0d5-812c37367b19",
    "status": "success",
    "created": "2015-02-16T17:35:03Z",
    "start": "2015-02-16T17:35:04Z",
    "end": "2015-02-16T17:35:07Z"
}
```

Get the most recent push to this target. This push may be a failure or success.

This endpoint returns a **Release** resource. see [Releases](#releases) for more information about the **Release** resource.

### HTTP Request

`GET https://hal.computer/api/targets/{id}/current-release(?status=success)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the target
status    | **Optional** - Filter by status. Can be used to get the most recent successful push.

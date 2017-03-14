# Targets

A deployment target in the **Hal domain model** is not an action, but a resource.

For example, a **server** + a **path** is a target. This allows multiple applications to be deployed to the same server.
For AWS-based deployments, **servers** are regions, so targets are the deployment details such as the AWS service
(such as Elastic Beanstalk application name, or CodeDeploy source S3 bucket, etc).

Targets are unique pairings between a server and an application.

### Attributes

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
id                   | Unique server ID                                 | number      | `502`
name                 | **Optional** - Name given to this target         | string,null | `test-target`
url                  | **Optional** - Full URL to access application    | string      | `http://server1.example.com`
configuration        | Configuration properties                         | object      |
pretty_name          | Pretty name formatted for humans                 | string      | `qltestserver`
detail               | Pretty name formatted with details               | string      | `Internal (Rsync): /test/path`
server               | **Embedded** - Server this target belongs to     | resource    |
application          | **Link** - Application for this target           |             |
pushes               | **Link** - List of pushes to this target         | list        |
current_release      | **Link** - Currently deployed release            | list        |

The following configuration properties are located under the `configuration` attribute.

#### RSync Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
path                 | Fully qualified file path                        | string,null | `/example/path`

#### AWS CodeDeploy Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
cd_name              | Application name                                 | string,null | `testapp`
cd_group             | Deployment group                                 | string,null | `testapp-deploy1`
cd_configuration     | Deployment configuration                         | string,null | `CodeDeployDefault.AllAtOnce`
s3_bucket            | Bucket name Configuration                        | string,null | `test-bucket`
s3_file              | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`


#### AWS Elastic Beanstalk Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
eb_name              | Application name                                 | string,null | `DemoApplication`
eb_environment       | Environment name                                 | string,null | `e-mvbatnpyzv`
s3_bucket            | Bucket name Configuration                        | string,null | `test-bucket`
s3_file              | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`

#### AWS S3 Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
s3_bucket            | Bucket name Configuration                        | string,null | `test-bucket`
s3_file              | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`

#### Script Configuration

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
script_context       | Context data passed to deploy scripts            | string,null | `server-pool1`

## Get All Targets

```http
GET /api/applications/24/targets HTTP/1.1
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

$response = $client->get('/api/applications/24/targets');
```

```shell
curl "https://hal.computer/api/applications/24/targets"
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
                "href": "https://hal.computer/api/targets/501",
                "title": "EB (e-mvbatnpyzv)"
            },
            {
                "href": "https://hal.computer/api/targets/502",
                "title": "S3 (bucket-name)"
            },
            {
                "href": "https://hal.computer/api/targets/503",
                "title": "localhost"
            }
        ],
        "self": {
            "href": "https://hal.computer/api/applications/24/targets"
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
GET /api/targets/502 HTTP/1.1
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

$response = $client->get('/api/targets/502');
```

```shell
curl "https://hal.computer/api/targets/502
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
            "href": "https://hal.computer/api/targets/502",
            "title": "S3 (bucket-name)"
        },
        "application": {
            "href": "https://hal.computer/api/applications/24",
            "title": "Hal Agent"
        },
        "pushes": {
            "href": "https://hal.computer/api/targets/502/pushes"
        },
        "current_release": {
            "href": "https://hal.computer/api/targets/502/current-release"
        }
    },
    "_embedded": {
        "server": {
            "id": 1234
            //server
        }
    },
    "id": 502,
    "name": "",
    "url": "http://example.com",
    "configuration": {
        "path": null,
        "cd_name": null,
        "cd_group": null,
        "cd_configuration": null,
        "eb_name": null,
        "eb_environment": null,
        "s3_bucket": "bucket-name",
        "s3_file": "testapp-24/$PUSHID.tar.gz"
    },
    "pretty_name": "S3 (us-east-1)",
    "detail": "S3: bucket-name/testapp-24/$PUSHID.tar.gz"
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
GET /api/targets/502/current-release HTTP/1.1
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

$response = $client->get('/api/targets/502/current-release');
```

```shell
curl "https://hal.computer/api/targets/502/current-release
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
            "href": "https://hal.computer/api/pushes/p2.5tqQFTF",
            "title": "p2.5tqQFTF"
        },
        "user": {
            "href": "https://hal.computer/api/users/3001",
            "title": "SKluck"
        },
        "target": {
            "href": "https://hal.computer/api/targets/502",
            "title": "localhost"
        },
        "build": {
            "href": "https://hal.computer/api/builds/b2.5KXaayW",
            "title": "b2.5KXaayW"
        },
        "application": {
            "href": "https://hal.computer/api/applications/24",
            "title": "Hal Agent"
        },
        "events": {
            "href": "https://hal.computer/api/pushes/p2.5tqQFTF/events"
        },
        "page": {
            "href": "https://hal.computer/pushes/p2.5tqQFTF",
            "type": "text/html"
        }
    },
    "id": "p2.5tqQFTF",
    "status": "Success",
    "created": "2015-02-16T17:35:03Z",
    "start": "2015-02-16T17:35:04Z",
    "end": "2015-02-16T17:35:07Z"
}
```

Get the most recent push to this target. This push may be a failure or success.

This endpoint returns a **Push** resource. see [Pushes](#pushes) for more information about the **Push** resource.

### HTTP Request

`GET https://hal.computer/api/targets/{id}/current-release(?status=Success)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the target
status    | **Optional** - Filter by status. Can be used to get the most recent successful push.

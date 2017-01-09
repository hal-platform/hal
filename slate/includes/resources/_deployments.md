# Deployments

A deployment in the **Hal domain model** is not an action, but a resource. It represents a **deployment target**.

For example, a **server** + a **path** is a target. This allows multiple applications to be deployed to the same server.
For AWS-based deployments, **servers** are regions, so deployments are the deployment details such as the AWS service
(such as Elastic Beanstalk application name, or CodeDeploy source S3 bucket, etc).

Deployments are unique pairings between a server and an application.

### Attributes

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
id                   | Unique server ID                                 | number      | `502`
name                 | **Optional** - Name given to this deployment     | string,null | `test-deployment`
url                  | **Optional** - Full URL to access application    | string      | `http://server1.example.com`
pretty-name          | Pretty name formatted for humans                 | string      | `qltestserver`
detail               | Pretty name formatted with details               | string      | `Internal (Rsync): /test/path`
server               | **Embedded** - Server this deployment belongs to | resource    |
application          | **Link** - Application for this deployment       |             |
pushes               | **Link** - List of pushes to this deployment     | list        |
last-push            | **Link** - Last push                             | list        |
last-successful-push | **Link** - Last successful push                  |             |

#### For RSync only

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
path                 | Fully qualified file path                        | string,null | `/example/path`

#### For CodeDeploy only

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
cd-name              | Application name                                 | string,null | `testapp`
cd-group             | Deployment group                                 | string,null | `testapp-deploy1`
cd-configuration     | Deployment configuration                         | string,null | `CodeDeployDefault.AllAtOnce`

#### For Elastic Beanstalk only

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
eb-name              | Application name                                 | string,null | `DemoApplication`
eb-environment       | Environment name                                 | string,null | `e-mvbatnpyzv`

#### For S3, CodeDeploy, and Elastic Beanstalk only

Attribute            | Description                                      | Type        | Example
-------------------- | ------------------------------------------------ | ----------- | -------------
s3-bucket            | Bucket name Configuration                        | string,null | `test-bucket`
s3-file              | File name (may include directories)              | string,null | `testapp/$PUSHID.tar.gz`

## Get All Deployment Targets

```http
GET /api/applications/24/deployments HTTP/1.1
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

$response = $client->get('/api/applications/24/deployments');
```

```shell
curl "https://hal9000/api/applications/24/deployments"
```

> ### Response

```json
{
    "_links": {
        "deployments": [
            {
                "href": "https://hal9000/api/deployments/501",
                "title": "EB (e-mvbatnpyzv)"
            },
            {
                "href": "https://hal9000/api/deployments/502",
                "title": "S3 (bucket-name)"
            },
            {
                "href": "https://hal9000/api/deployments/503",
                "title": "localhost"
            }
        ],
        "self": "https://hal9000/api/applications/24/deployments"
    },
    "count": 3
}
```

Get all deployments for a specific application.

### HTTP Request

`GET https://hal9000/api/applications/{id}/deployments`

## Get Deployment Target

```http
GET /api/deployments/502 HTTP/1.1
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

$response = $client->get('/api/deployments/502');
```

```shell
curl "https://hal9000/api/deployments/502
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/deployments/502",
            "title": "S3 (bucket-name)"
        },
        "application": {
            "href": "https://hal9000/api/applications/24",
            "title": "Hal Agent"
        },
        "pushes": {
            "href": "https://hal9000/api/deployments/502/pushes"
        },
        "last-push": {
            "href": "https://hal9000/api/deployments/502/last-push"
        },
        "last-successful-push": {
            "href": "https://hal9000/api/deployments/502/last-push?status=Success"
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
    "path": null,
    "cd-name": null,
    "cd-group": null,
    "cd-configuration": null,
    "eb-name": null,
    "eb-environment": null,
    "s3-bucket": "bucket-name",
    "s3-file": "testapp-24/$PUSHID.tar.gz",
    "url": "http://example.com",
    "pretty-name": "S3 (us-east-1)",
    "detail": "S3: bucket-name/testapp-24/$PUSHID.tar.gz"
}
```

Get details for a specific deployment.

### HTTP Request

`GET https://hal9000/api/deployments/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the deployment

## Get Last Push to Deployment

```http
GET /api/deployments/502/last-push HTTP/1.1
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

$response = $client->get('/api/deployments/502/last-push');
```

```shell
curl "https://hal9000/api/deployments/502/last-push
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/pushes/p2.5tqQFTF",
            "title": "p2.5tqQFTF"
        },
        "user": {
            "href": "https://hal9000/api/users/3001",
            "title": "SKluck"
        },
        "deployment": {
            "href": "https://hal9000/api/deployments/502",
            "title": "localhost"
        },
        "build": {
            "href": "https://hal9000/api/builds/b2.5KXaayW",
            "title": "b2.5KXaayW"
        },
        "application": {
            "href": "https://hal9000/api/applications/24",
            "title": "Hal Agent"
        },
        "logs": {
            "href": "https://hal9000/api/pushes/p2.5tqQFTF/logs"
        },
        "page": {
            "href": "https://hal9000/pushes/p2.5tqQFTF",
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

Get the most recent push to this deployment. This push may be a failure or success. In addition, the status can be
filtered to only get the most recent **successful** push.

This endpoint returns a **Push** resource. see [Pushes](#pushes) for more information about the **Push** resource.

### HTTP Request

`GET https://hal9000/api/deployments/{id}/last-push(?status=Success)`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the deployment
status    | **Optional** - Filter by status. Can be used to get the most recent successful push.

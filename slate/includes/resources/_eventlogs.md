# Event Logs

During actions such as **Builds** and **Pushes**, events are recorded and kept to assist with debugging, auditing, etc.
These event logs typically have a status, message, and some amount of additional data. For example, when running
build commands, `stdout` will be recorded, and also `stderr` if an error occurs.

Please note that many logs can be recorded at the same time, so time is an unreliable indicator of log order.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique event log ID                                 | string   | `e34a3e76d2a44ff7a3c776999dee0708`
event           | Event during which this log was recorded            | string   | `push.start`
order           | Order of the log                                    | number   | `2`
message         | Log message                                         | string   | `Build compiled successfully`
status          | Status of this message                              | string   | `success`
created         | Time log was created in ISO 8601                    | string   | `2016-01-06T20:45:36Z`
data            | Context data                                        | object   |
build           | **Link, Optional** - Parent build of this log       | resource |
push            | **Link, Optional** - Parent push of this log        | resource |

## Get Event Log

```http
GET /api/eventlogs/e34a3e76d2a44ff7a3c776999dee0708 HTTP/1.1
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

$response = $client->get('/api/eventlogs/e34a3e76d2a44ff7a3c776999dee0708');
```

```shell
curl "https://hal9000/api/eventlogs/e34a3e76d2a44ff7a3c776999dee0708"
```

> ### Response

```json
{
    "_links": {
        "self": {
            "href": "https://hal9000/api/eventlogs/e34a3e76d2a44ff",
            "title": "[1] Resolved push properties"
        },
        "push": {
            "href": "https://hal9000/api/pushes/p2.5tqQFTF",
            "title": "p2.5tqQFTF"
        }
    },
    "id": "e34a3e76d2a44ff",
    "event": "push.start",
    "order": 1,
    "message": "Resolved push properties",
    "status": "success",
    "created": "2016-01-12T17:35:04Z",
    "data": {
        "Method": "rsync",
        "Location": {
            "path": "/temp/build/hal9000-push-p2.5tqQFTF",
            "archive": "/archive/hal9000-b2.5tqD2TB.tar.gz",
            "tempArchive": "/temp/build/hal9000-push-p2.5tqQFTF.tar.gz",
            "tempZipArchive": "/temp/build/hal9000-push-p2.5tqQFTF.zip"
        }
    }
}
```

Description description desc description.

### HTTP Request

`GET https://hal9000/api/eventlogs/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the event log

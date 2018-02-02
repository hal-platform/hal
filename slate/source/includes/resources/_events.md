# Events

During actions such as **Builds** and **Pushes**, events are recorded and kept to assist with debugging, auditing, etc.
These events typically have a status, message, and some amount of additional data. For example, when running
build commands, `stdout` will be recorded, and also `stderr` if an error occurs.

Please note that many events can be recorded at the same time, so time is an unreliable indicator of event order.

### Attributes

Attribute       | Description                                         | Type     | Example
--------------- | --------------------------------------------------- | -------- | -------------
id              | Unique event ID                                     | string   | `e34a3e76-d2a4-4ff7-a3c7-76999dee0708`
name            | Event name                                          | string   | `release.start`
order           | Order of the event                                  | number   | `2`
message         | Event message                                       | string   | `Build compiled successfully`
status          | Status of this message                              | string   | `success`
created         | Time event was created in ISO 8601                  | string   | `2016-01-06T20:45:36Z`
data            | Context data                                        | object   |
job             | **Link** - Parent build or release of this event    | resource |

## Get Event

```http
GET /api/job-events/e34a3e76-d2a4-4ff7-a3c7-76999dee0708 HTTP/1.1
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

$response = $client->get('/api/job-events/e34a3e76-d2a4-4ff7-a3c7-76999dee0708');
```

```shell
curl "https://hal.computer/api/job-events/e34a3e76-d2a4-4ff7-a3c7-76999dee0708"
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
            "href": "https://hal.computer/api/job-events/e34a3e76-d2a4-4ff7-a3c7-76999dee0708",
            "title": "[1] Resolved release properties"
        },
        "job": {
            "href": "https://hal.computer/api/releases/f95def5f-bb7f-4643-adbf-ece49d3d7317",
            "title": "f95def5f-bb7f-4643-adbf-ece49d3d7317"
        }
    },
    "id": "e34a3e76-d2a4-4ff7-a3c7-76999dee0708",
    "name": "release.start",
    "order": 0,
    "message": "Resolved release properties",
    "status": "success",
    "created": "2016-01-12T17:35:04Z",
    "data": {
        "Method": "rsync",
        "Location": {
            "path": "/temp/build/hal-release-f95def5f-bb7f-4643-adbf-ece49d3d7317",
            "archive": "/archive/hal-71d7e2cd-f375-49bf-bb69-7ce0dac45558.tar.gz",
            "tempArchive": "/temp/build/hal-release-f95def5f-bb7f-4643-adbf-ece49d3d7317.tar.gz",
            "tempZipArchive": "/temp/build/hal-release-f95def5f-bb7f-4643-adbf-ece49d3d7317.zip"
        }
    }
}
```

Description description desc description.

### HTTP Request

`GET https://hal.computer/api/job-events/{id}`

### URL Parameters

Parameter | Description
--------- | -----------
id        | The unique ID of the event

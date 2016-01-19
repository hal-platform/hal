## Errors


### Error Codes

The Hal API uses the following error codes:

Code       | Meaning
----------:| -------
400        | **Bad Request** - Your request sucks
403        | **Forbidden** - The specified endpoint requires a token, or if provided it is invalid.
404        | **Not Found** - Resource not found.
415        | **Unsupported Media Type** - The provided content is invalid or not supported.
429        | **Too Many Requests** - You've made too many requests! Slow down!
500        | **Internal Server Error** - Clearly a human error has caused Hal to abort your request to prevent further damage. Try again or contact an administrator.
502        | **Bad Gateway** -  A dependent system such as the web server is misbehaving.
503        | **Service Unavailable** -  Hal is currently undergoing maintanance. Please try again later.
504        | **Gateway Timeout** -  A dependent system such as the web server is misbehaving.

> ### Example - Missing Resource

```json
{
    "status": 404,
    "title": "Not Found",
    "detail": "Resource Not Found"
}
```

> ### Example - Errors

```json
{
    "status": 500,
    "title": "Internal Server Error",
    "detail": "Notice: Undefined variable: test"
}
```

```json
{
    "status": 400,
    "title": "Bad Request",
    "detail": "Malformed Datetime! Dates must be ISO8601 UTC."
}
```

### Error Response

When an error or otherwise exceptional situation occurs, and the response is rendered as a **problem**. This is a resource
type defined in the draft IETF RFC [Problem Details for HTTP APIs](https://datatracker.ietf.org/doc/draft-ietf-appsawg-http-problem/)

`Content-Type: application/problem+json`

### Attributes

Attribute       | Description                                                                              | Type
--------------- | ---------------------------------------------------------------------------------------- | ------
status          | The HTTP status code                                                                     | number
title           | A short, human-readable summary of the problem type.                                     | string
detail          | **Optional** - An human readable explanation specific to this occurrence of the problem. | string
type            | **Optional** - A URI reference that identifies the problem type.                         | string

## Hypermedia

The Hal API uses the **Hypertext Application Language** hypermedia standard. Hypermedia is an important component
of building a RESTful API. It enables developers to build more discoverable APIs by **linking** between resources.

For every successful response, Hal will use the Content-Type `application/hal+json`. Please note <b>HAL</b> in this context
standards for the hypermedia standard and **is not unique to the Hal application**.

> ### Example HAL Resource

```json
{
    "_links": {
        "self": {
            "href": "https://hal.computer/api/endpoint"
        },
        "link_name": {
            "href": "https://hal.computer/api/resource/1234"
        },
        "link_name2": {
            "href": "https://external.example.com/html_page",
            "title": "Optional title",
            "type": "Optional content type"
        },
        "list_of_links": [
            {
                "href": "https://hal.computer/api/resource/1",
                "title": "Optional title"
            },
            {
                "href": "https://hal.computer/api/resource/2"
            }
        ]
    },
    "_embedded": {
        "resource_name": {
            "id": "def",
            "property_name": "test"
        },
        "list_of_resources": [
            {
                "id": "sub_resource_1"
            },
            {
                "id": "sub_resource_1"
            }
        ]
    },
    "id": "abc",
    "property_name": false,
    "test_property_name": "example"
}
```

Resources with the HAL mediatype **MAY** use the following reserved properties.

Property  | Description
--------- | -----------
_links    | An object whose property names are link relation types and values are either a Link Object or an array of Link Objects
_embedded | An object whose property names are link relation types and values are either a Resource Object or an array of Resource Objects

Clients that find these properties when parsing responses from the Hal API **SHOULD** parse them as a standard **HAL mediatype**.

<aside class="warning">
    Errors do not follow this standard. See <a href="#error-responses">Errors</a> for details on how to parse error responses.
</aside>

See also:

- [HAL Standard Working Draft](https://datatracker.ietf.org/doc/draft-kelly-json-hal/)
- [HAL Working Draft - Link Objects](https://tools.ietf.org/html/draft-kelly-json-hal-07#section-5)
- [Wikipedia - HATEOAS](https://en.wikipedia.org/wiki/HATEOAS)
- [Wikipedia - HAL](https://en.wikipedia.org/wiki/Hypertext_Application_Language)

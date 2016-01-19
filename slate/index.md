---
title: Hal - API Reference

language_tabs:
  - http: HTTP
  - php: PHP
  - shell: cURL

toc_footers:
  - <a href='https://hal9000/api'>API Home</a>
  - <a href='http://github.com/tripit/slate'>Documentation Powered by Slate</a>

includes:
  - introduction/authentication
  - introduction/errors
  - introduction/hypermedia
  - introduction/domain
  - endpoints/index
  - resources/applications
  - resources/environments
  - resources/groups
  - resources/servers
  - resources/users
  - endpoints/queue

search: true
---

# Introduction

This is documentation for the Hal API. You can use the API to access resources such as Applications, Servers, Deployments, Builds and Pushes.

<aside class="notice">
    <b>Please Note</b> -
    This API is primary used for reads. Write functionality is currently limited to <b>Creating Builds</b>. We plan to expand this functionality in the future.
</aside>

There are language examples in **HTTP**, **PHP**, and **cURL**. You can view code examples in the dark area to the right, and you can switch the programming language of the examples with the tabs in the top right.

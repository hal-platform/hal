---
title: Hal - API Reference

language_tabs:
  - http: HTTP
  - php: PHP
  - shell: cURL

toc_footers:
  - <a href='https://hal9000/api'>Live API</a>
  - <a href='https://hal9000/settings'>Generate an API Token</a>
  - <a href='http://github.com/tripit/slate'>Documentation Powered by Slate</a>

includes:
  - meta/authentication
  - meta/errors
  - meta/hypermedia
  - meta/domain
  - endpoints/index
  - resources/applications
  - resources/organizations
  - resources/environments
  - resources/groups
  - resources/targets
  - resources/builds
  - resources/builds_create
  - resources/releases
  - resources/releases_create
  - resources/events
  - resources/users
  - endpoints/queue
  - meta/changelog

search: true
---

# Introduction

This is documentation for the Hal API. You can use the API to access resources such as Applications, Groups, Deployments, Builds and Pushes.

<aside class="notice">
    <b>Please Note</b> -
    This API is primary used for reads. Write functionality is currently limited to <b>Running Builds</b> and <b>Deploying Releases</b>. We plan to expand this functionality in the future.
</aside>

There are language examples in **HTTP**, **PHP**, and **cURL**. You can view code examples in the dark area to the right, and you can switch the programming language of the examples with the tabs in the top right.

<aside class="warning">
    Any endpoint prefixed with <code>/api/internal/</code> is designated for <b>internal Hal use only</b>. It is not for public use and may change for any reason at any time. Such endpoints are not documented here.
</aside>

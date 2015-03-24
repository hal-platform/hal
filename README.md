# HAL 9000

- [Initial HAL 9000 Setup](#initial-hal-9000-setup)
- [Updating HAL 9000 after a release](#updating-hal-9000-after-a-release-or-pull)
- [Making Front-end Changes](#making-front-end-changes)
- [Redis caching](#redis-caching)

## Initial HAL 9000 Setup

#### Database

HAL 9000 uses a MySQL database as a back-end. In order to develop HAL 9000 you will need to install MySQL (or MariaDB) on your system.

The default connection settings for HAL:

Setting   | Default
--------- | -------
server    | `localhost`
database  | `hal`
username  | `root`

These can be changed in your `config.env.yml`.

The schema is located in the `hal-core` package which contains doctrine repositories and models for all hal deployments. Run the phinx migrations included with `hal-core`.

Sometimes the schema changes between releases. Make sure to run the migrations included with `hal-core` in the release notes to stay up to date.

#### Server

Create a vhost configuration for your web server with the following settings:

Setting   | Value
--------- | -------
root      | `/path/to/hal/public`
index     | `/path/to/hal/public/index.php`

#### Application configuration

1. Run `bin/install` to install PHP, Node, and Ruby dependencies.
2. Run `bin/normalize-configuration`.

This will copy `app/environment/config.dev.yml` to `app/config.env.yml`. You may edit this file, as it is gitignored and will not be committed.

#### Application compilation

1. Run `bin/gulp build`.

This will process and optimize the CSS and JS.

#### Permissions within HAL

HAL Administrators can add and update any repository. If you are not in the Web Core AD group, you can set `debug.godmode` to your user ID which will give you these permissions.

GitHub collaborators and HAL Admins can push any repository in non-prod environments. For production, an AD group must be setup with the correct permissions.

## Updating HAL 9000 after a release or pull

#### Update site dependencies

1. Run `bin/install` to ensure you have the correct php, npm, and ruby dependencies.
2. Run `bin/gulp build` to rebuild the assets.

#### Database

Sometimes the schema changes between releases. Make sure to run the migrations included with `hal-core` to stay up to date. Changes may also be made between commits while a release is being developed.

If your database schema seems out of date, talk to another developer and find out the necessary steps to update your local schema.

## Making Front-end Changes

Only make changes in `/js`, `/img`, and `/sass` directories. Don't make changes in the public directory.

#### Watch for changes

To launch HAL in your browser and watch for file changes run `bin/gulp serve`. This will watch for template, javascript, html, and sass changes and automatically reload the browser on save.

For live reload to actually work you either need to install a livereload browser plugin or put this in `app/templates/base.html.twig`:
```
<script src="http://localhost:35729/livereload.js"></script>
```

Just don't commit with that script still on the page.

#### Build the front-end so it is ready to deploy.

Run `bin/gulp --deploy` to delete `public/js`, `public/img`, `public/css` and rebuild them all with production-ready settings.

The `deploy` flag will perform further minifying and compressing that are not done in the standard dev compilation.

## Server setup

HAL 9000 requires the following environment:

#### PHP 5.6+

The following extensions must be installed:

- `ext-apc`
- `ext-curl`
- `ext-json`
- `ext-intl`
- `ext-ldap`
- `ext-mbstring`
- `ext-mcrypt`
- `ext-openssl`
- `ext-PDO`
- `ext-pcntl`
- `ext-pdo_mysql`
- `ext-pdo_sqlite`
- `ext-SimpleXML`
- `ext-xmlwriter`
- `ext-zip`

#### NGINX/FPM or Apache

#### MySQL Database

#### Redis server for caching

## Redis caching

Some data is cached to redis. Most caching is done through `MCP\Cache` and can be disabled easily through configuration.

All redis usage is prefixed with a namespace such as `hal9000dev` or `hal9000`.

namespace   | Usage                           | Default TTL
----------- | ------------------------------- | --------------
api         | Cached responses for `/api`     | 10 seconds
github      | Enterprise github api requests  | 60 seconds
permissions | LDAP user,group lookups         | 10 minutes
doctrine    | Doctrine cache                  | 5 minutes

#### Example cache keys:

**API**
```
hal9000:mcp-cache:api:e057d4ea363fbab414a874371da253dba3d713bc
```

**Github**
```
hal9000:mcp-cache:github:e057d4ea363fbab414a874371da253dba3d713bc
hal9000:mcp-cache:github:e057d4ea363fbab414a874371da253dba3d713bc.etag
hal9000:mcp-cache:github:e057d4ea363fbab414a874371da253dba3d713bc.modifiedsince
```

**Permissions**
```
hal9000:mcp-cache:permissions:github.1234.5678
hal9000:mcp-cache:permissions:ldap.group.58fd9edd83341c29f1aebba81c31e257
hal9000:mcp-cache:permissions:ldap.user.58fd9edd83341c29f1aebba81c31e257
```

**Doctrine**
```
hal9000dev:doctrine:dc2_5f2088971e6d3bffd2058aa1bbc0c4d5_[QL\Hal\Core\Entity\Server$CLASSMETADATA][1]
hal9000dev:doctrine:DoctrineNamespaceCacheKey[dc2_5f2088971e6d3bffd2058aa1bbc0c4d5_]
```

#### Non-default cache times

For API calls:

`build info` and `push info` are cached for 5 seconds instead of the default of 10.
`queue` and `queue refresh` are never cached.

For Github calls:

[Pull Request](https://developer.github.com/v3/pulls/#list-pull-requests) and [Git Reference](https://developer.github.com/v3/git/refs/#get-a-reference) data are cached for 10 seconds instead of the default of 60.


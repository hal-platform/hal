# Hal Deployment Platform

- [Development Setup](#development-setup)
- [Server Requirements](#server-requirements)

## Development Setup

1. Clone the project.

2. Set up database.
    > Hal uses a MySQL or Postgres database backend. Ensure one of these is installed and
    > update the `database.*` properties in your config with connection details.
    >
    > Schema and migrations are handled by `hal-core` package. You will need to separately clone `hal-core` and run the phinx migrations.

3. Set up a web server (such as NGINX).
    > Set up your vhost to point to `../public/index.php` and `../public` as your doc root.

4. Run `bin/install` to install PHP and Node dependencies.

5. Copy `configuration/environment/dev.yml` to `configuration/config.env.yml`
    > Run `bin/normalize-configuration` to do this automatically.

6. Update `configuration/config.env.yml` with any specific details for your local dev environment.
7. Run `yarn run build`.
    > Runs frontend (css, js) code build process and compilation to optimize assets.

## Server Requirements

Hal requires the following server environment:

- **NGINX/FPM**
- **MySQL or Postgres database**
- **Redis server for caching**
- **PHP 7.1+**
    - The following extensions must be installed:
    - `ext-apc`
    - `ext-curl`
    - `ext-json`
    - `ext-intl`
    - `ext-ldap`
    - `ext-libsodium`
    - `ext-mbstring`
    - `ext-openssl`
    - `ext-PDO`
    - `ext-pcntl`
    - `ext-pdo_mysql`
    - `ext-pdo_sqlite`
    - `ext-SimpleXML`
    - `ext-xmlwriter`
    - `ext-zip`

# Hal

- [Hal Development Setup](#hal-development-setup)
- [Server Requirements](#server-requirements)
- [Proprietary Dependencies](#proprietary-dependencies)
- [Encryption](#encryption)

## Hal Development Setup

#### Database

Hal uses a MySQL database as a back-end. In order to develop Hal you will need to install MySQL (or MariaDB) on your system.

The default connection settings for Hal:

Setting   | Default
--------- | -------
server    | `localhost`
database  | `hal`
username  | `root`

These can be changed in your `config.env.yml`.

The schema is located in the `hal-core` and `kraken-core` packages which contains doctrine repositories and models for all hal deployments. Run the phinx migrations included with `hal-core` then in `kraken-core`.

Sometimes the schema changes between releases. Make sure to run the migrations included with `hal-core` and `kraken-core` in the release notes to stay up to date.

#### Web server

Create a vhost configuration for your web server with the following settings:

Setting   | Value
--------- | -------
root      | `/path/to/hal/public`
index     | `/path/to/hal/public/index.php`

#### Application configuration

1. Run `bin/install` to install PHP and Node dependencies.
2. Run `bin/normalize-configuration`.

This will copy `app/environment/config.dev.yml` to `app/config.env.yml`. You may edit this file, as it is gitignored and will not be committed.

#### Application compilation

1. Run `npm run build`.

This will process and optimize the CSS and JS.

## Server Requirements

Hal requires the following server environment:

- **PHP 5.6+**
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
- **NGINX/FPM or Apache**
- **MySQL Database**
- **Redis server for caching**

## Proprietary Dependencies

#### Frontend

- `mcp-logger` (Core Logger)
- `mcp-corp-account` (Login auth, profile data)
- `mcp-crypto` (Cookie encryption, Kraken encryption)
- `mcp-qks` (Kraken encryption)
- `mcp-cache`(Caching)
- `ql/panthor-plugins` (Panthor/MCP Integration)

#### Agent

- `mcp-logger` (Core logger)

## Encryption

The Hal frontend application uses encryption for three purposes:

#### 1. Symmetric Encryption

For sensitive data at rest in the Hal database.
This encryption method requires **libsodium** and uses `TamperResistantPackage`.

- Kraken Sensitive Values
    - The secret is stored in a file on the filesystem: `configuration/kraken.encrypter.secret`
- Cookies
    - The secret is stored in configuration: `%cookie.encryption.secret%`

#### 2. Assymetric Encryption

For values that must only be decrypted by the Hal Agents.
This encryption method requires **openssl** and uses `openssl_seal/openssl_open`. These values cannot be decrypted by the frontend.

- Encrypted Properties
- Credentials
    - Currently only used by AWS deployments (EB, EC2, S3)

#### 3. QKS Symmetric Encryption

For Kraken configuration sent to Consul.
This encryption method requires **libsodium** and uses `QuickenMessagePackage`.

Kraken configuration is encrypted while at rest in the Hal db. When configuration is deployed each property is re-encrypted using the QKS crypto packaging.

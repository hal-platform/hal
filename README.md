# HAL 9000 #

- [Initial HAL 9000 Setup](#initial-hal-9000-setup)
- [Updating HAL 9000 after a release](#updateing-hal-9000-after-a-release)
- [Making Front-End Changes](#making-front-end-changes)

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

The schema is located in the `hal-core` package which contains doctrine repositories and models for all hal deployments. Run the queries in the schema file: `hal-core/config/initial.mysql`.

Sometimes the schema changes between releases. Make sure to run the migration queries in the release notes to stay up to date.

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

Sometimes the schema changes between releases. Make sure to run the migration queries in the release notes to stay up to date. Changes may also be made between commits while a release is being developed.

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


# HAL 9000 #

- [Set up HAL 9000 for development](#set-up-hal-9000-for-development)

## Set up HAL 9000 for development

#### MySQL

HAL 9000 uses a MySQL database as a back-end. In order to develop HAL 9000 you will need to install MySQL (or MariaDB)
on your system.

1. [Download](http://dev.mysql.com/downloads/mysql/) and install MySQL.
2. Make sure the root MySQL user can access the database via localhost with no password (this is local dev after all).
3. Create a database called `hal`.
4. Run the queries in the `app/reqs/initial.mysql` file.

#### Apache

The next thing you need is to setup apache. Your vhost should look something like this:

    <VirtualHost *:80>
      ServerName hal9000.local
      DocumentRoot /path/to/hal/public
      <Directory /path/to/hal/public>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ index.php [QSA,L]
      </Directory>
    </VirtualHost>

The above vhost assumes your git clone is in `/path/to/hal`. Adjust for your setup accordingly.

You'll have to make sure that whatever is in the `ServerName` directive resolves to your IP address in
your `hosts` file.

#### Application configuration

Copy `app/config.yml.dist` to `app/config.yml` in your project root and make sure the values inside are correct.

#### Sudo permissions

The user your web server runs as needs to be able to sudo the push command. You can give the user access to only this file in `sudoers`:

```
<WEB-SERVER-USER> ALL=(<YOUR-USER-ACCOUNT>) NOPASSWD:SETENV:<PATH-TO-HAL-PUSH-SCRIPT>
```
- `<WEB-SERVER-USER>` is a local user account, such as `www-data` or `_www`.
- `<YOUR-USER-ACCOUNT>` is your Hal user account, which should be the same as your LDAP and local user account.
    To allow any user to push, use `ALL`.
- `<PATH-TO-HAL-PUSH-SCRIPT>` is the full path to the push script, for example `/projects/hal/bin/pusher.php`.
    Wildcards are supported so you may use `/projects/hal/bin/*` to whitelist the whole directory.
- The flag `NOPASSWD` is required because Hal does not use passwords to run the command.
- The flag `SETENV` is required because Hal sets environment variables for build/post-push scripts to utilitize.

#### SSH

Hal will attempt to ssh into your target server and sync files or run deployment commands. If developing locally,
ensure you can ssh without a password into `localhost`.

```bash
ssh -v <USERNAME>@localhost
```

If you are prompted for a password, try adding your public key to your `authorized_keys`:
```bash
cat ~/.ssh/id_rsa.pub >> ~/.ssh/authorized_keys
```

#### Repository permissions

GitHub collaborators and hal admins can push any repository in non-prod environments. For production,
an AD group must be setup with the correct permissions.

### Making Front-End Changes

#### Install Dependencies

* Install node modules: `npm install`
* Install gems `gem install bundler` then `bundle install`

Only make changes in /js, /img, and /sass directories. Don't make changes in the public directory.

#### Watch for changes

To launch Hal in your browser and watch for file changes run `gulp serve`. This will watch for template, javascript, html, and sass changes and automatically reload the browser on save.

For live reload to actually work you either need to install a livereload browser plugin or put this in app/templates/base.html.twig: `<script src="http://localhost:35729/livereload.js"></script>`. Just don't commit with that script still on the page.

#### Build the front-end so it is ready to deploy.

Run `gulp --deploy` to delete public/js, public/img, public/css and rebuild them all with production-ready settings.

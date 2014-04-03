# HAL 9000 #

## Contributing ##

HAL 9000 uses a MySQL database as a back-end. In order to develop HAL 9000 you will need to install MySQL (or MariaDB) on your system.

1. [Download](http://dev.mysql.com/downloads/mysql/) and install MySQL.
2. Make sure the root MySQL user can access the database via localhost with no
   password (this is local dev after all).
3. Create a database called `hal`.
4. Run the queries in the `app/reqs/initial.mysql` file.
5. Copy `app/config.yml.dist` to `app/config.yml` in your project root and make sure the values inside are correct.

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

You'll have to make sure that whatever is in the `ServerName` directive resolves to your IP address.

### Caveats:

In addition, if you choose to work in your local environment, there are some permission issues you may run into
since Hal 9000 relies heavily on shell commands and moving files around.

#### Sudo permissions

The user your web server runs as needs to be able to sudo the push command. You can give the user access to only this file in `sudoers`:

```
<WEB-SERVER-USER> ALL=(<YOUR-USER-ACCOUNT>) NOPASSWD :<PATH-TO-HAL-PUSH-SCRIPT>
```
- `<WEB-SERVER-USER>` is a local user account, such as `www-data` or `_www`.
- `<YOUR-USER-ACCOUNT>` is your Hal user account, which should be the same as your LDAP and local user account.
- `<PATH-TO-HAL-PUSH-SCRIPT>` is the full path to the push script, for example `/projects/hal/bin/pusher.php`.
    Wildcards are supported so you may use `/projects/hal/bin/*` to whitelist the whole directory.

#### Repository push permissions

`QL\Hal\PushPermissionService` controls whether a specific user has push permissions for repositories. At this time,
these permissions cannot be changed from Hal itself. To whitelist for development, change the method
`canUserPushToEnvRepo` to always return true.

#### Sync command variables

In `QL\Hal\SyncHandler`, environment variables are set before running the push command. Your local shell
environment may or may not support the method in which these are set. In `syncCmdCreate`, if you are running into
problems, comment out the following code:
```php
foreach ($envVars as $k => $v) {
    $cmdEnvs .= escapeshellarg($k) . '=' . escapeshellarg($v) . ' ';
}
```

#### Sync command path

You will probably want to sync to a local directory rather than a remote server.

In `QL\Hal\PushCommand`, update the `runPush` method to only use the path as the sync target.

Change this:
```php
$target = sprintf(
    '%s@%s:%s',
    $touser,
    $tohost,
    $topath
);
```

to this:
```php
$target = $topath;
```
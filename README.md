# HAL 9000 #

## Deploying Your Application with HAL 9000 ##

So, you want to use HAL 9000 to deploy your application? There are a few things you need to do.

1.  Get your application into out Github Enterprise server, if it's not already.
2.  Learn how to use the HAL 9000 web interface - send an email to webcore@quickenloans.com and we can show you how.
3.  Contact Keymasters and request that push permission groups be created. These groups will be used to determine who
    can push your application to test and beta. Note that only keymasters can push to prod. Alternatively, you can
    skip this step and HAL will give permission to any users who are listed as collaborators on your Github repository.
4.  Collect the required information:
    -   What environments and servers will your application be deployed to? (test1www1, test1beta1, etc.)
    -   What directory on those machines will your application live in? (/var/www/application, etc.)
    -   What command, if any, do you use to build your application? This is commonly a shell script that lives within
        your repository and tells HAL what should be done prior to pushing your code. It must return 0 on success
        or 1 on failure.
        - This command has access to the following environment variables.
            - `HAL_ENVIRONMENT`: The environment your application was built for (typically test, beta, or prod)
    -   What command, if any, do you need to run after deploying your application? This is called a post-push command
        and is commonly used to restart services, fix permissions, or finalize deployment. It must return o on success
        and 1 on failure.
        - This command has access to the following environment variables.
            - `HAL_HOSTNAME`: The hostname of the remote server.
            - `HAL_ENVIRONMENT`: The environment the application was built for.
            - `HAL_PATH`: The path the application was pushed to.
            - `HAL_COMMIT`: The Git commit that was built.
            - `HAL_GITREF`: The Git branch or tag name that was built.
            - `HAL_BUILDID`: The unique build ID.
5.  Take the above required information and ask a HAL 9000 admin to add your repository. There are already HAL admins
    on many teams that you can speak with. If you don't know any, then feel free to email webcore@quickenloans.com.

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

`QL\Hal\PushPermissionService` controls whether a specific user has push permissions for repositories. GitHub
collaborators and hal admins can push any repository in non-prod environments. For production, an AD group must
be setup with the correct permissions.

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

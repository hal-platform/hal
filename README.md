# HAL 9000 #

## Contributing ##

HAL 9000 uses a MySQL database as a back-end. In order to develop HAL 9000 you will need to install MySQL (or MariaDB) on your system.

1. [Download](http://dev.mysql.com/downloads/mysql/) and install MySQL.
2. Make sure the root MySQL user can access the database via localhost with no
   password (this is local dev after all).
3. Create a database called `hal`.
4. Run the queries in the `reqs/initial.mysql` file.
5. Copy conf.ini.dist to conf.ini in your project root and make sure the values inside are correct.

The next thing you need is to setup apache. Your vhost should look something like this:

    <VirtualHost *:80>
      ServerName hal9000.local
      DocumentRoot /Users/mnagi/code/hal/public
      <Directory /Users/mnagi/code/hal/public>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ /Users/mnagi/code/hal/public/index.php [QSA,L]
      </Directory>
    </VirtualHost>

The above vhost assumes your git clone is in `/Users/mnagi/code/hal`. Adjust for your setup accordingly.

You'll have to make sure that whatever is in the `ServerName` directive resolves to your IP address.

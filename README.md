# GITBERT2 #

## Assumptions ##

Note that any mention of characters means Unicode characters unless otherwise specified.

 - Common ID's stored in Active Directory will only be numbers and never be more than 9 digits.
 - Windows usernames will never be greater than 32 characters.
 - Environment names are not greater than 16 characters that match `[a-z0-9_-]`.
 - We will never have more than 255 environments (ie test, beta and prod are 3 environments).
 - Target servers will never have hostnames greater than 32 ASCII characters. Hostnames must follow DNS style grammar.
 - All repositories will be hosted on an instance of Github Enterprise.
 - Absolute paths to push to on target servers can be no longer than 255 characters.

## Contributing ##

GITBERT 2 uses a MySQL database as a back-end. In order to develop GITBERT 2,
you will need to install MySQL on your system.

1. Install MySQL.
2. Make sure the root MySQL user can access the database via localhost with no
   password (this is local dev after all).
3. Create a database called `hal`.
4. Run the queries in the `reqs/initial.mysql` file.

MySQL for GITBERT2 is now setup.

The next thing you need is to setup apache. Your vhost should look something like this:

    <VirtualHost *:80>
      ServerName hal9000.local
      DocumentRoot /Users/mnagi/code/hal/public
      SetEnv DB_DSN mysql:unix_socket=/tmp/mysql.sock;dbname=hal;charset=utf8
      SetEnv DB_USER root
      SetEnv DB_PASS 
      <Directory /Users/mnagi/code/hal/public>
        RewriteEngine On
        RewriteBase /
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteRule ^ /Users/mnagi/code/hal/public/index.php [QSA,L]
      </Directory>
    </VirtualHost>

The above vhost assumes your git clone is in `/Users/mnagi/code/hal`.
Adjust for your setup accordingly.

Between these two things, you should be able to run GITBERT2 locally.

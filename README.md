# iPC Catalogue Outbox api

### Dependencies
- Web server  (e.g. Apache2 Nginx)
- PHP and composer
- MongoDB

### Usage

### Installation

##### 1. Clone the repository
##### 2. Install 3rd party modules:
Use `composer` to install the PHP libraries required to run the application
```
composer update
```
Install PHP extension for CURL
`apt install php-curl`
systemctl restart apache2.service

Install MongoDB PHP extension (https://docs.mongodb.com/php-library/current/tutorial/install-php-library/)
```
apt install php-dev
pecl install mongodb
echo "extension=mongodb.so" >> /etc/php/7.2/apache2/php.ini
composer require mongodb/mongodb
systemctl restart apache2.service
```

##### 3. Create log file:
Create and empty file that the application is going to use as log file. Make sure that the UNIX user of the web server has write permissions:
```
touch logs/app.log
chmod 777 logs/app.log
```

##### 4. Configure web access:

The web server (e.g. Apache2 or Nginx) is to be set so that the `public/` folder of the application is accessible from the internet. In the following example, the **DocumentRoot** of an Apache2 server is pointing to the installation path :

```
<VirtualHost *:80>

        ServerName my.site.es

        ServerAdmin webmaster@localhost
        DocumentRoot /var/www/html

        Alias "/catalogue_outbox/api/" "/path-to-api/public/"

        <Directory "/path-to-api/public/">
                Options Indexes FollowSymLinks
                AllowOverride All
                Require all granted
        </Directory>

        ErrorLog ${APACHE_LOG_DIR}/error.log
        CustomLog ${APACHE_LOG_DIR}/access.log combined

</VirtualHost>
```

```
sudo systemctl restart apache2
```

##### 4.Configure application:
Configure all the particulars of the installation at `app/settings.php`. Take as template the following file:

```
cp app/settings.php.sample   app/settings.php

```
Configure the following key parameters:
- settings.logger : Configure the log module
- settings.db: Configure the connection with the Mongo database
- globals.dataDir: Path to the api data folder (userdata).
- globals.api:  OAuth2 endpoints of the OIDC authentication server

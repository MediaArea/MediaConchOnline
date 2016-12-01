# MediaConchOnline README

MediaConch is an open source software project consisting of a toolset that aims to further develop the standardization and validation of preservation-level audiovisual files used within various memory institutions and communities. The software consists of an implementation checker, policy checker, reporter and fixer that will work together to offer its users an advanced level of ability to validate, assess and find solutions to repair the digital files within their collections. Accessible via either the command line, a graphical user interface (GUI), or web-based shell, the MediaConch project will serve to provide detailed individual and batch-level conformance checking analysis using its adaptable, flexible and interoperable software application interface. With a project focus dedicated to furthering the longevity of Matroska, Linear Pulse Code Modulation (LPCM) and FF Video Codec 1 (FFV1) as recommended digital preservation audiovisual formats, MediaConch will anticipate and contribute to the further development of the standardization of these formats. The MediaConch open source project was created and is currently under development by MediaArea, notable for the creation of MediaInfo, an open source media checker software application.

MediaConchOnline is a web application for MediaConch.

Website: <a href="https://mediaarea.net/MediaConch/">:shell: MediaConch project</a>.

A MediaConchOnline instance is available on MediaArea website : [MediaConchOnline](https://mediaarea.net/MediaConchOnline/)

# Table of Repositories

#### [MediaConch](https://github.com/MediaArea/MediaConch)
The original repository for the MediaConch project, this repository holds all public documentation related to Phase I of the project (the design phase) and some metadata-related work.

#### [MediaConch_SourceCode](https://github.com/MediaArea/MediaConch_SourceCode)
This repository hosts the source code for MediaConch, the GUI.

#### [MediaConchOnline](https://github.com/MediaArea/MediaConchOnline)
This is the source code for MediaConchOnline, the online version of the MediaConch shell.

#### [MediaConch-Website](https://github.com/MediaArea/MediaConch-Website)
This is the repository for content hosted on [https://mediaarea.net/MediaConch/](https://mediaarea.net/MediaConch/).

#### [MediaAreaXml](https://github.com/MediaArea/MediaAreaXml)
This repository holds XSD (XML Schema Definitions) for MediaConch, MediaInfo, and MediaTrace.

#### [MediaConch_SampleFiles](https://github.com/MediaArea/MediaConch_SampleFiles)
This repository contains sample files used to test MediaConch.

#### [MediaConch_MKVSurvey](https://github.com/MediaArea/MediaConch_MKVSurvey)
This repository holds a research corpus used in the development of the MediaConch.

# Funding and Licensing

This project has received funding from PREFORMA, co-funded by the European Commission under its FP7-ICT Programme. All software and source code developed by MediaArea during the PREFORMA project will be provided under the following two open source licenses: GNU General Public License 3.0 (GPLv3 or later), Mozilla Public License (MPLv2 or later).

All open source digital assets for the software developed by MediaArea during the PREFORMA project will be made available under the open access license: Creative Commons license attribution – Sharealike 4.0 International (CC BY-SA v4.0). All assets will exist in open file formats within an open platform (an open standard as defined in the European Interoperability Framework for Pan-European eGovernment Service (version 1.0 2004)).

# How to install

## Dependencies

* Apache web server >= 2.2 (should also work on nginx and lighttpd)
    * mod rewrite is recommended
* Php >= 5.6
    * Mandatory packages for debian-like distributions : libapache2-mod-php5, php5-cli, php5-intl, php5-mysqlnd, php5-curl
    * Mandatory packages for RedHat-like distributions : php, php-cli, php-intl, php-mbstring, php-mysql, php-pdo, php-process, php-xml
    * Optional packages for RedHat-like distributions : php-pecl-apc or php-opcache
    * date.timezone parameter should be set in your php.ini (both cli and apache module)
* MySQL >= 5.1
* [MediaConch-Server](https://mediaarea.net/MediaConch/download.html) >= 16.11 (depends on [libmediainfo >= 0.7.91](https://mediaarea.net/en/MediaInfo/Download) and [libzen >= 0.4.34](https://mediaarea.net/en/MediaInfo/Download))
* [Composer](https://getcomposer.org/download/)

## Get MediaConchOnline sourcecode

### From git

Clone MediaConchOnline repository
```
git clone https://github.com/MediaArea/MediaConchOnline.git
```

### From tarball

Download and extract tarball
```
wget "https://mediaarea.net/download/source/mediaconch/16.11/MediaConchOnline_16.11.tar.xz"
tar -Jxf MediaConchOnline_16.11.tar.xz
```

## Configure MediaConchOnline

### MySQL

Create a new user for MediaConchOnline (you can also use an existing one).
Create a new database for MediaConchOnline (you can also use an existing one).
Give privilege for your user to your database.

```
CREATE USER 'YOUR_USER'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT USAGE ON * . * TO 'YOUR_USER'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
CREATE DATABASE IF NOT EXISTS `YOUR_DATABASE`;
GRANT ALL PRIVILEGES ON `YOUR_DATABASE` . * TO 'YOUR_USER'@'localhost';
```

### Project configuration

#### From git

Enter project directory and run composer to install dependencies and configure the project (parameters are explained below)
```
cd YOUR_PATH/MediaConchOnline/
composer install
```

#### From tarball

Edit the parameters (explanation below) in parameters.yml file : app/config/parameters.yml

#### Parameters

* database_driver (pdo_mysql): driver to access database server, leave blank to use MySQL
* database_host (127.0.0.1): host of the database server, leave blank if the database server is on the same host than the web server
* database_port (null): port of the database server, leave blank if database server is running on standard port
* database_name (symfony): database for MediaConchOnline
* database_user (root): user for MediaConchOnline database
* database_password (null): password for MediaConchOnline database
* mailer_transport (smtp): mailer server to send emails, leave blank
* mailer_host (127.0.0.1): mailer server host, leave blank
* mailer_user (null): mailer server user, leave blank
* mailer_password (null): mailer server password, leave blank
* locale (en): locale of the project, only english is supported for now, leave blank
* secret (ThisTokenIsNotSoSecretChangeIt): a unique string for your MediaConchOnline instance, used to generating CSRF tokens and encrypt cookies, fill it with a random string (at least 32 characters is recommended)
* mco_check_folder (/some/folder/): a directory containing files to test in "Check server files", set to null to disable this feature
* mco_ga_tracking (null): Google Analytics tracking code, use tracking id (UA-XXXXXXXX-X) or null to disable this feature

You can change these parameters after by editing this file : app/config/parameters.yml

#### Create database tables

```
app/console doctrine:schema:update --force
```

#### Add an admin user
```
app/console fos:user:create YOUR_ADMIN_USER YOUR_EMAIL@domain.com --super-admin
```

#### Create a directory to store user policies files and give it rights to apache user
```
mkdir files && sudo chown YOUR_APACHE_USER files
```

### Apache

Add a vhost to access MediaConchOnline, like this minimal example :
```
<VirtualHost *:80>
    ServerName WWW.YOURWEBSITE.COM
    DocumentRoot "YOUR_PATH/MediaConchOnline/web/"
    <Directory "YOUR_PATH/MediaConchOnline/web/">
        AllowOverride All
        Options -Indexes
        <IfModule mod_authz_core.c>
          # Apache 2.4
            Require all granted
        </IfModule>
        <IfModule !mod_authz_core.c>
          # Apache 2.2
            Order allow,deny
            allow from all
        </IfModule>
    </Directory>
</VirtualHost>

```
Allow apache user to write in cache and log directory, some methods are explain in [Symfony documentation](https://symfony.com/doc/2.7/book/installation.html#checking-symfony-application-configuration-and-setup)

### MediaConch-Server

To configure MediaConch-Server refer to the [config documentation](https://github.com/MediaArea/MediaConch_SourceCode/blob/master/Documentation/Config.md) and [server configuration](https://github.com/MediaArea/MediaConch_SourceCode/blob/master/Documentation/Daemon.md)

Update MediaConchOnline config according to your MediaConch-server config :
```
# app/config/config.yml
app:
    mediaconch:
        address: 127.0.0.1
        port: 4242
        api_version: 1.12
```

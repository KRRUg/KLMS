# System Requirements and Setup
The KLMS environment consists of two applications:
The [IDM Server](https://github.com/KRRUg/IDM) to store your user-data in a central location
and one KLMS instance for each of your events.

:point_right: For production setup, we will provide a docker setup shortly. If you need support right now, please contact us.

## System requirements
Recommended and tested are a Linux Server running Apache and the following versions:
 * PHP 8.1
 * PostgreSQL 9 or 12
 * [Composer 2](https://getcomposer.org/download/)

For the build process the following tools are required (they are not required to run on the server):
 * Node.js 19
 * [Yarn v1](https://classic.yarnpkg.com/lang/en/docs/install/)

For development, no Apache webserver is required. See [development setup](Development Setup) below.

## Production Setup
Assuming you have a running and configured Apache webserver with PHP 8.1 and PostgreSQL database.
For instructions on how to configure apache to serve a symfony application, please consult the
[Symfony documentation](https://symfony.com/doc/5.4/setup/web_server_configuration.html).
Make sure that all required PHP extensions (`ctype`, `dom` `gd`, `iconv`,`json`,`pgsql`,`libxml`, and `intl`) are installed and enabled.

We also assume that there is a running [IDM Server](https://github.com/KRRUg/IDM) instance
(usually listening on localhost on the local server).

### Database setup
Login as the PostgreSQL admin user (usually `postgres`) and create a user
with an according password and create a database for the KLMS instance.

Running Linux and logged on as root, the following commands perform this actions:
```
sudo -u postgres -i
createuser -l -P <db_user>
createdb -O <db_user> <db_name>
exit
``` 

### KLMS setup
This guide follows loosely the [Symfony deployment instructions](https://symfony.com/doc/5.4/deployment.html).

First, clone the KLMS source code in the web folder (e.g. `/var/www/klms`).

Then create the local env file to tell the framework the database and IDM connections.
Create a file `.env.local` in the project's main directory with the following content:
```
APP_ENV=prod
APP_SECRET=<some random string>

DATABASE_URL=postgresql://<db_user>:<db_pw>@<db_ip>:<db_port>/<db_name>?serverVersion=12&charset=utf8
KLMS_IDM_URL=https://<idm_host>:<idm_port>
KLMS_IDM_APIKEY=<idm_key>

SITE_BASE_SCHEME=https
SITE_BASE_HOST=<your_url>:<your_port>
```

There are two optional config steps to enable email sending and/or Recaptcha protection for your registration page. 
```
MAILER_DSN=smtp://<user>:<passwd>@<mailserver>:587
MAILER_DEFAULT_SENDER_NAME='<name of your event>'
MAILER_DEFAULT_SENDER_EMAIL=noreply@<yourdomain.com>

EWZ_RECAPTCHA_SECRET=<your-recaptcha-secret>
EWZ_RECAPTCHA_SITE_KEY=<your-recaptcha-site-key>
```

To set up the required third party libraries go to the project directory and run
```shell
composer install --no-dev
composer dump-autoload --no-dev --classmap-authoritative
``` 

Next, build the front-end. This is the only step that requires node and can be done on a different machine.
(In case you ran this on a different machine, copy the generated `public/build` folder to same location on the server)
```shell
yarn install
yarn encore prod
```

To create the database schema and finalize the installation, run in the following commands in your KLMS source dir (`bin/console` is a PHP script provided by KLMS):
```
bin/console doctrine:schema:create
bin/console cache:clear
```

Open your site's URL in your browser. You should see an empty KLMS instance.
To start configuring your site, log in with a super-admin account of your IDM instance to get all permissions on the KLMS instance at once.

To send emails and perform maintenance tasks, KLMS requires the PHP script `bin/console messenger:consume` to run on the server.
We recommend to use supervisor to ensure the process is restarted whenever it ends.

## Development Setup
Follow the [Symfony setup guidelines](https://symfony.com/doc/5.4/setup.html) and install the symfony binary.

When running `symfony check:requirements` ensure that no errors nor warnings are displayed.
In addition to the basic Symfony setup, install: PostgreSQL server, Node.js, and Yarn.

Follow the setup steps for a production setup (as shown above) without the `--no-dev` arguments.
No apache setup is required, as Symfony provides a development server. 

To fill the database, with test-data, run
```
bin/console doctrine:fixtures:load
```

Once all setup steps are done start the Symfony development server using
```
symfony server:start --port=8000 --no-interaction --no-tls
```
Open the printed URL in your browser and log in with a super-admin credential
(in case have been following the IDM development setup, those are `admin@admin.local` with password `admin`).

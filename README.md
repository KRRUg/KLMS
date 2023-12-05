# KLMS
KRRU LAN-Party Management System

## Development setup

### Installation
Follow the Symfony setup guidelines to set up a basic Symfony installation: https://symfony.com/doc/4.4/setup.html

When running `symfony check:requirements` ensure that no errors nor warnings are displayed.
In addition to the basic Symfony setup, install:
* PostgreSQL server
* PHP PostgreSQL module
* Node.js
* Yarn (see https://yarnpkg.com/getting-started/install/)  

KLMS is tested and running in Production with the following Versions:
* PHP 8.1
* PostgreSQL 9 & 12
* Node.js 19
* Yarn 1
* Composer 2

### Docker based development setup
We provide a docker-compose-based setup for running Apache 2, PHP 8 and PostgreSQL 12. 
Make sure you have cloned KLMS and [IDM](https://github.com/krrug/IDM) in the same folder, located next to each other. 

Run `docker-compose up` to run the webserver, database and mailcatcher.

Follow the [_KLMS setup_](#KLMS%20setup) steps below and come back here.

Then follow the IDM installation steps and return here.

Create a `.env.local` with the updated database string:
```yml
DATABASE_HOST=database
DATABASE_URL=postgresql://app:app@${DATABASE_HOST}:5432/klms?serverVersion=12&charset=utf8
```
Setting the host is required since docker-compose containers are linked to each other and connections happen through their hostnames.

Additionally, you need two local DNS entries. For Linux/MacOS edit the file `/etc/hosts` with root permissions. Windows entries can be found in the `C:\Windows\System32\drivers\etc` folder, also use elevated permissions for editing.
```
127.0.0.1 klms.local identity.local
```
Further database setup below is not necessary.

Restart docker-compose to apply the updated environment file. 

You should be able to access http://klms.local

### Database setup
Log in as the PostgreSQL admin user (usually `postgres`), create a user
with an appropriate password and the database for the KLMS instance.

Running Linux and logging on as root, the following commands perform these actions:
```bash
sudo -u postgres -i
createuser -l -P <db_user>
createdb -O <db_user> <db_name>
exit
``` 

### KLMS setup
First create the local env file to tell the framework the database and IDM connections.
Create a file `.env.local` in the project's main directory with the following content:
```
DATABASE_URL=postgresql://<db_user>:<db_pw>@<db_ip>:<db_port>/<db_name>?serverVersion=12&charset=utf8
KLMS_IDM_URL=https://<idm_host>:<idm_port>
KLMS_IDM_APIKEY=<idm_key>
```

To set up the required third-party libraries go to the project directory and run
```bash
composer install
yarn install
yarn encore dev
``` 

To create the database schema and some default data run
```bash
bin/console doctrine:database:create
bin/console doctrine:schema:create
bin/console doctrine:fixtures:load -n
```

### Run KLMS
Once all setup steps are done start the Symfony development server using
```bash
bin/console server:start
```
Open the printed URL in your browser and log in with a superuser credential 

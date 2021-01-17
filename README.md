# KLMS
KRRU LAN-Party Management System

## Development setup

### Instalation
Follow the Symfony setup guidelines to set up a basic Symfony installation: https://symfony.com/doc/4.4/setup.html

When running `symfony check:requirements` ensure that no errors nor warnings are displayed.
In addition to the basic Symfony setup, install:
* PostgreSQL server
* PHP PostgreSQL module
* Node.js
* Yarn (see https://yarnpkg.com/getting-started/install/)  

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
First create the local env file to tell the framework the database and IDM connections.
Create a file `.env.local` in the project's main directory with the following content:
```
DATABASE_URL=postgresql://<db_user>:<db_pw>@<db_ip>:<db_port>/<db_name>?serverVersion=11&charset=utf8
KLMS_IDM_URL=https://<idm_host>:<idm_port>
KLMS_IDM_APIKEY=<idm_key>
```

To set up the required third party libraries go to the project directory and run
```
composer install
yarn install
yarn encore dev
``` 

To create the database schema and some default data run
```
bin/console doctrine:schema:create
bin/console doctrine:fixtures:load -n
```

### Run KLMS
Once all setup steps are done start the symfony development server using
```
bin/console server:start
```
Open the printed URL in your browser and login in with a superuser credential 

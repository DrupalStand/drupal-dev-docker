# Docker/Drupal Development Environment

This project is the start of a highly opinionated development environment using 
a combination of ideas and strategies from many other opinions across the community.

Cloning this repository will give you a stock Drupal development site running in 
docker containers. GNU Make commands assist in setup, management, and cleanup.

Files are synced locally allowing you to do local development work and work with 
Composer without having to be in the server instance.

## Requirements

Composer: [install composer](https://getcomposer.org/doc/00-intro.md#installation-linux-unix-osx).

> Note: The Makefile requires a [global composer installation](https://getcomposer.org/doc/00-intro.md#globally).

Docker: [install docker](https://www.docker.com/products/overview).

## Usage

Assuming the requirements are installed and setup (that is, Composer is in your path 
and the Docker service is running), then you can jump right into it:

```
make init
```

This command will take a long time if this is the first time you're running it.

The init command runs through almost all of the available individual commands and 
will result in an environment ready to work with. The environment can be accessed via 
http://localhost:8080. MySQL and memcached are also accessible on their default ports 
(3306 and 11211, respectively).

The Makefile contains many additional commands that can be individually executed 
to perform certain actions on a case by case basis:

```
make [command]
```

* **`init-drupal`** Meta command to execute `drupal-install config-init config-import clear-cache`.
* **`docker-rebuild`** Rebuilds the docker containers from build files.
* **`docker-status`** Prints the status of docker.
* **`docker-start`** Brings up the docker environment and displays status.
* **`docker-stop`** Brings down the docker environment.
* **`composer-install`** Runs composer install against root composer.json.
* **`composer-upgrade`** Runs composer upgrade against root composer.json. This 
will upgrade all files tracked by composer. Use with caution. To only upgrade Drupal 
core, use `drupal-upgrade`.
* **`drupal-upgrade`** Updates Drupal core.
* **`drupal-install`** Executes a drush based site install.
* **`config-init`** Sets UUID of the system config to the UUID of the newly created 
Drupal site. This is nescessary to bring configuration data between environments 
without bringing the database along at the same time. Configuration should be in ./config.
* **`config-import`** Imports the configuration data into Drupal. This will fail 
if `config-init` has not been run first. Configuration should be in ./config.
* **`config-export`** Exports config out of Drupal into ./config.
* **`config-validate`** Verifies config before import.
* **`config-refresh`** Meta command to execute `config-init config-import`.
* **`salt`** Generates a random 64 digit salt for Drupal to utilize.
* **`clear-cache`** Executes a drush based cache rebuild.
* **`destroy`** Brings down the docker environment, removes the database, and deletes 
Drupal's files. Depending on configuration, this command may need to be executed as root.
* **`rebuild`** Meta command to execute `destroy init`. Depending on configuration, 
this command may need to be executed as root.

## Details

* Docker-compose is building the environment with off the shelf components. The 
official images from the PHP, MySQL, and memcache teams are used. The PHP image 
is slightly modified at build time to accound for where the code is hosted inside 
of the image (/var/www/web instead of /var/www/html).
* Docker for OSX doesn't do well with file sharing so we utilize the [bg-sync](https://github.com/cweagans/docker-bg-sync) 
container to help. This means that occasionally files may take a little bit to sync 
between the host and the docker instance.
* Data for databases will be maintained through docker reboots as long as the "database"
volume is not deleted manually or with the "-v" option on `docker-compose down`.
* This repository *does* track a composer.lock file meaning that versions of software 
installed are the ones that match the lock file. Currently this repository tracks 
the base installation provided by the [drupal-composer/drupal-project](https://github.com/drupal-composer/drupal-project) 
with Drupal 8.1.10. You can upgrade with `make drupal-upgrade` or wait until this 
repository is updated. Certain files such as those provided by the Drupal scaffolding 
project may be subject to manipulation and may or may not upgrade cleanly.
* The Drupal site installed by default is configured with the "minimal" profile 
which has absolutely zero configuration out of the box. If you do not import config, 
the site will be setup with the "Stark" theme. The installation profile used can 
be set in the Makefile under the `drupal-install` target, however, if you use any 
configuration, it's recommended that you leave it "minimal".

## Drush/Drupal Console

Custom implementations for drush and the Drupal Console have been created to allow 
communication with these tools inside the running instances. You can execute commands 
using `bin/drush` and `bin/drupal`. These commands are special scripts that work 
with the `drush` and `drupal` installed by composer but from the inside of your docker 
container so that they can access your environments running resources.

If you want to do management while docker is not running (which is not recommended), 
then you can use `/vendor/bin/drush` or `/vendor/bin/drupal`.

## TODO

* Upgrade to newly released Drupal 8.2.x.
* Separate configuration from Drupal's settings.php file into local-based files.
* Integrate the option for other Docker-based containers to assist with specific 
tasks such as ElasticSearch.
* Modify base PHP instance to expose xdebug so that users can utilize debugging 
software such as PHPStorm.

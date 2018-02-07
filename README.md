# Goddard Development Environment

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
https://localhost:8443. MySQL and memcached are also accessible on their default ports 
(3306 and 11211, respectively).

The Makefile contains many additional commands that can be individually executed 
to perform certain actions on a case by case basis:

```
make [command]
```

* **`init-drupal`** Meta command to execute `drupal-install config-init config-import clear-cache`.
* **`update`** Meta command to execute `docker-stop composer-install docker-rebuild config-import clear-cache`.
Use this command after a git pull has been performed to ensure that infrastructure 
and configuration match the repository. This will destroy any uncommited Drupal configuration.
* **`safe-update`** Meta command to execute `docker-stop composer-install docker-rebuild clear-cache`.
Use this command after a git pull has been performed to ensure that infrastructure 
matches the repository. This will not overwrite Drupal configuration.
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
* **`lint`** Tests custom modules and themes against PHP Parallel Lint.
* **`sniff`** Tests custom modules and themes against Drupal coding standards 
and best practices using PHP CodeSniffer.
* **`code-test`** Meta command to execute `lint sniff`.
* **`code-fix`** Automatically fixes some errors identified via `code-test` utilizing 
functionality built into PHP CodeSniffer via PHP Code Beautifier and Fixer.
* **`fix-permissions`** Sets appropriate permissions in the working directory ensuring
that the current user is able to edit files.

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
with Drupal 8.4.3. You can upgrade with `make drupal-upgrade` or wait until this 
repository is updated. Certain files such as those provided by the Drupal scaffolding 
project may be subject to manipulation and may or may not upgrade cleanly.
* The Drupal site installed by default is configured with the "minimal" profile 
which has absolutely zero configuration out of the box. If you do not import config, 
the site will be setup with the "Stark" theme. The installation profile used can 
be set in the Makefile under the `drupal-install` target, however, if you use any 
configuration, it's recommended that you leave it "minimal".
* This repository tracks a local development file. It is included automatically 
if the file exists. If this code base is used in production, ensure that there 
is a deployment process in place to remove this file before going live.

## Drush/Drupal Console

Custom implementations for drush and the Drupal Console have been created to allow 
communication with these tools inside the running instances. You can execute commands 
using `bin/drush` and `bin/drupal`. These commands are special scripts that work 
with the `drush` and `drupal` installed by composer but from the inside of your docker 
container so that they can access your environments running resources.

If you want to do management while docker is not running (which is not recommended), 
then you can use `vendor/bin/drush` or `vendor/bin/drupal`.

## Development Workflow

This environment contains most tools needed to perform day-to-day development 
activities, but some steps and make targets might be vague. These are some of the 
common use cases and how the environment can be used to satisfy them.

### Initialization

To begin work, clone the repository. Once the repository is cloned and the 
prerequisites are met (composer and docker installed locally), a single command 
can be used to initialize the environment:

1. `make init`

The environment will go through many steps and should leave you with a working 
Drupal installation. You should be able to access the site at [https://localhost:8443](https://localhost:8443).

### Destruction

If an environment becomes unstable, or you wish to start again with a fresh 
environment straight from your code base, consider destroying the environment. 
A destroy will erase all content in your local site, so be sure there are no 
configuration items or content assets you wish to preserve.

1. `make destroy`

The destruction command will ask you to confirm your password to ensure it has 
proper permissions to remove all files. Once performed, the environment can be 
setup again with the initialization step.

### Rebuild

If you wish to do a destroy and an initialization in the same command, a command 
exists to merge these steps together:

1. `make rebuild`

This will perform the actions of `make destoy` followed by `make init`.

### Stopping/Starting An Environment

Environments can be stopped and frozen for later use. This can be useful if 
resources need to be opened up. Stopping an environment will not destroy any of 
the content or configuration in the environment.

1. `make docker-stop`

In a similar fashion, the environment can be restarted:

1. `make docker-start`

Once the environment has started, the site should again be reachable. These 
commands will be rarely used in day-to-day development.

### Installing A Composer Library

Composer is used to manage all PHP packages tracked by the environment. It is 
used for libraries as well as other PHP packages. By default, installed composer 
packages install to the `/vendor` directory. Special packages (such as Drupal 
modules) may be installed to different directories. These directories are not 
tracked in git and should not be added to your SCM.

This is an example adding [PHP Code Sniffer](https://packagist.org/packages/squizlabs/php_codesniffer) 
to your environment.

1. `composer require squizlabs/php_codesniffer`
2. `git add composer.json composer.lock`
3. `git commit -m "Added PHP_CodeSniffer to environment."`

Note that the files downloaded by Composer and placed in `/vendor` are not 
tracked. The composer.json and composer.lock files contain enough information 
for other environments to obtain the same code the next time their environments 
are built.

### Installing A Drupal Module

Composer is also used to install Drupal modules, themes, profiles, and 
libraries. The end directory for these packages varies depending on the type 
of package being required, however, like the `/vendor` directory, these 
directories are ignored in git and should not be added to your SCM.

This is an example adding [Webform](https://www.drupal.org/project/webform) to 
your environment and activating it.

1. `composer require drupal/webform`
2. `bin/drush en webform`
3. `make config-export`
4. `git add composer.json composer.lock config/core.extension.yml`
5. `git commit -m "Added Drupal Webform to environment."`

These steps are similar to a normal composer package, but the environment needs 
to be aware that Drupal has a module to enable. After it's installed, it is 
enabled using drush. After it has been enabled, Drupal's configuration is 
exported. In this example, there was no configuration of the module after the 
installation, but be mindful that there might be additional configuration files 
to add after a module has been enabled. Lastly, this configuration, along with the 
composer.json and composer.lock files, are added to the code management.

### Creating Custom Code

If a custom module or theme is needed, the Drupal Console will help facilitate 
creating the boilerplate for these items. The [manual for Drupal Console](https://hechoendrupal.gitbooks.io/drupal-console/content/en/index.html) 
can be helpful during these steps. The console can also be used to create 
boilerplate for existing modules and plugins.

This is an example creating a module called "Example Module":

1. `bin/drupal generate:module --module "Example Module"`
   * The Drupal Console will confirm a few bits of information
2. `git add web/modules/custom/example_module`
3. `git commit -m "Created a new module, Example Module"`

The code that comes out of this generation will be owned by the root user which 
often means it will not be editable. Consider running the `make fix-permissions` 
command after creation to rectify this.

### Exporting Configuration

Drupal ships with an extensive configuration tracking interface known as 
[CMI](http://drupal8cmi.org/). Configuration for almost everything can be 
exported and is tracked in flat yaml files stored in `/config`. The environment 
will automatically import these configuration files during a `make init` or 
`make update` target.

It is important to validate that the configuration exported is the configuration 
that you were expecting to export:

1. `make config-validate`

This command will list out all of the configuration files inside the `/config` 
directory that are different than configuration in Drupal. If the configuration 
that is listed reflects the changes you have made, then it is safe to export the 
configuration and commit the results.

This is an example changing the theme of the site:

1. `bin/drush config-set system.theme default bartik`
2. `make config-validate`
   * Ensure that the output of this contains only related configuration.
3. `make config-export`
4. `git add /config`
5. `git commit -m "Set Bartik theme as default."`

In this example, the configuration modified was done from drush, but almost all 
configuration done in the interface will also be tracked this way. Keep the 
configuration commits simple and often otherwise the quantity of files can 
become very large very quickly.

Note that all configuration that does not match what is in the `/config` 
directory will be exported. This does not mean that all of it needs to be 
committed. In the example above, the `/config` directory is blanket added to git 
but this might not always be the case. Take care to only commit what is 
nescessary to the work at hand.

The cleanest and most effective way to modify configuration for the site is to 
commit each configuration change as it is performed:

1. Make a single change (or related set of changes) to Drupal
2. Validate and export configuration
3. Add and commit exported configuration to SCM
4. Repeat for other changes

### Updating Code

When working with a team, upstream code will often be newer than local code. In 
order to keep the environment up to date, there are a few commands to facilitate 
this depending on the situation and the current state of the environment.

If an environmnet has no uncommitted configuration or code changes in it, the 
`make update` command should be used:

1. `git status`
   * Ensure the environment is clean
2. `git pull`
3. `make update`

This command will update the infrastructure, install/update packages, and reset 
Drupal's configuration to the configuration tracked in code.

If an environment has uncommitted work in it, caution should be taken when 
updating to ensure there are no conflicts. The `make safe-update` command exists 
for this purpose:

1. `git pull`
2. `make safe-update`

This command will update the infrastructure and install/update packages, but 
will not touch Drupal's configuration. This will help preserve any work 
currently being done in the environment.

When possible, update during a state of clean development. Between the two 
commands, `make update` should be used more often.

### Problems With Permissions

The environment runs in two different fashions concurrently: locally and in 
Docker containers. Due to the nature of Docker containers, most items created 
inside these containers are owned by the user running the services inside the 
container. This user is usually root.

While this is good for ensuring very little has to be done inside the container, 
it adds a challenge to modifying files that often sync between the container and
the local environment. These could be files such as settings.php or files 
touched by drush or Drupal Console.

Permission errors will typically be encountered during two different common 
operations:

1. Editing files that were created inside the container
2. Checking out code (changing branches or resetting files)

If permission errors are ever encountered, they can be rectified by running a 
single command:

1. `make fix-permissions`

If the environment contains a large amount of files, this command could take a 
while, but when it's done, there should be no more problems.

## TODO

* Integrate the option for other Docker-based containers to assist with specific 
tasks such as ElasticSearch.

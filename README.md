# DrupalStand Development Environment

The DrupalStand project is an opinionated local development environment built around
the concept of simplicity, transparency, expandability, and an _exceptionally_ low
barrier to entry. Built on docker, the DrupalStand project should function identically
on all* host systems capable of running docker images.

Cloning this repository will give you a stock Drupal development site running in 
docker containers. GNU Make commands assist in setup, management, and cleanup. Additional
commands can be easily added into the system to satisfy independent project needs.

Files are synced locally allowing you to do local development work using the tools and
functionality you and your developers are most comfortable with. A single codebase houses
all of the code needed to replicate an entire Drupal environment identically on multiple
machines with no configuration needed.

While focused on development tasks, the project also offers some production deployment
tools to ease the transition of a local codebase into a production ready deployment. These
tools prepare specially formulated containers with production in mind and export them into
standalone images that can be deployed into orchestration environments or spun up as 
standalone servers.

## Requirements

* MacOS, Linux, or Windows

> On systems that don't ship with docker-compose (Linux), it should also be installed.

* GNU Make (https://www.gnu.org/software/make/)

> Windows support can be achieved with WSL or WSL2 natively in this repository.
> We recommend WSL2 due to the excellent speed and support using `make` commands to manage Docker natively.

#### Windows Setup
Docker for Windows (https://www.docker.com/products/overview) will detect and use WSL2. Hypervisor is required for WSL **while it is not for WSL2**. For now, Windows 10 version 2004 and above are the only versions that have WSL2 available. Updates for Windows are being rolled out over the coming months, but to get it now, run the Update Assistant (see https://support.microsoft.com/en-us/help/3159635/windows-10-update-assistant)

For native-like speeds, add your code to a folder under the WSL2 OS you have set up. You may experience significant slowdown if you do not set up your project this way due to the fact that filesystem mounts in WSL2 are implemented as NFS. (see https://github.com/microsoft/WSL/issues/4197#issuecomment-650205399)

1. Install WSL (https://docs.microsoft.com/en-us/windows/wsl/install-win10)
2. Run Docker for Windows (https://docs.docker.com/docker-for-windows/install/)
3. Get the Linux distro you want (Debian in my case) from Windows Store.
4. If using WSL1,
   1. Use `docker-compose` for now to start and stop the project. You may need to read through the Makefile to find out what all is needed to run in order for the project to work correctly.
5. If using WSL2,
   1. Clone the repo from within WSL (e.g. Debian) but do NOT use the `/mnt/` folders. (https://github.com/microsoft/WSL/issues/4197)
   2. Configure your IDE as normal, but open the source under your `\\wsl$\` directory within Windows.
6. Run the native `make` commands per normal in the following section of this document.

> Do you have PHPStorm support?

PHPStorm will work fine out of box with xdebug and detect paths automatically, but in order to get PHP interpretation (and thus error detection), PHPStorm has support for Docker containers.

To enable it, open this project and then hit `File > Settings > Languages & Frameworks > PHP`. Under this dialog, add a "CLI Interpreter" (`...` button, then `+` button on the next dialog) - Docker will be an option, and it will automatically pull the available Docker containers. Select the one marked `drupal-dev-docker-php`.

> PHPStorm wants to open the Windows Command Prompt instead of WSL.

You can select WSL to run instead of the default DOS prompt. Open this project and then hit `File > Tools > Terminal` and under `Application settings`, enter `C:\Windows\System32\wsl.exe` as the `Shell path`.

> PHPStorm's terminal opens the home directory, `$`

Attempting to use a drive letter mapping will not work correctly. Open the project in PHPStorm using the path at `\\wsl$\`

## Usage

Once Docker is installed, the environment can be brought right up:

```
make init
```

This command could take a long time if this is the first time you're running it. Multiple
versions of this environment on the same machine (or repeat builds) will utilize image
caching to build faster subsequent times.

The init command runs through almost all of the available individual commands and 
will result in an environment ready to work with. The environment can be accessed via 
https://localhost:8443. MySQL and memcached are also accessible on their default ports 
(3306 and 11211, respectively).

The Makefile contains many additional commands that can be individually executed 
to perform certain actions on a case by case basis:

```
make [command]
```

* **`help`** Show available commands in the make file along with descriptions.
* **`init-drupal`** Meta command to execute `drupal-install config-init config-import clear-cache`.
* **`update`** Meta command to execute `docker-stop composer-install docker-rebuild config-import clear-cache`.
Use this command after a git pull has been performed to ensure that infrastructure 
and configuration match the repository. This will destroy any uncommitted Drupal configuration.
* **`safe-update`** Meta command to execute `docker-stop composer-install docker-rebuild clear-cache`.
Use this command after a git pull has been performed to ensure that infrastructure 
matches the repository. This will not overwrite Drupal configuration.
* **`docker-rebuild`** Rebuilds the docker containers from build files.
* **`docker-status`** Prints the status of docker.
* **`docker-start`** Brings up the docker environment and displays status.
* **`docker-stop`** Brings down the docker environment.
* **`docker-restart`** Meta command to run `docker-stop` and `docker-start`.
* **`composer-install`** Runs composer install against root composer.json.
* **`composer-upgrade`** Runs composer upgrade against root composer.json. This 
will upgrade all files tracked by composer. Use with caution. To only upgrade Drupal 
core, use `drupal-upgrade`.
* **`drupal-upgrade`** Updates Drupal core.
* **`drupal-install`** Executes a drush based site install.
* **`config-init`** Sets UUID of the system config to the UUID of the newly created 
Drupal site. This is necessary to bring configuration data between environments 
without bringing the database along at the same time. Configuration should be in ./config.
* **`config-import`** Imports the configuration data into Drupal. This will fail 
if `config-init` has not been run first. Configuration should be in ./config.
* **`config-export`** Exports config out of Drupal into ./config.
* **`config-validate`** Verifies config before import.
* **`config-refresh`** Meta command to execute `config-init config-import`.
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
* **`shell`** Opens a shell in the specified container as root.

## Details

* Docker-compose is building the environment with off the shelf components. The 
official images from the PHP, MySQL, and memcache teams are used. Some modifications
are made to the base images to enable things such as out of the box SSL encryption
and XDebug configuration.
* Data for databases will be maintained through docker reboots as long as the "database"
volume is not deleted manually or with the "-v" option on `docker-compose down`.
* This repository *does* track a composer.lock file meaning that versions of software 
installed are the ones that match the lock file. Currently this repository tracks 
the base installation provided by the [drupal/recommended-project](https://github.com/drupal/recommended-project) 
with Drupal 9.0.1. You can upgrade with `make drupal-upgrade` or wait until this 
repository is updated. Certain files such as those provided by the Drupal scaffolding 
project may be subject to manipulation and may or may not upgrade cleanly. The default
location for the hosted content in this project was moved from the default of `/web`
to `/webroot` to make this directory's purpose more clear.
* The Drupal site installed by default is configured with the "minimal" profile 
which has absolutely zero configuration out of the box. This environment, however,
ships with a small amount of configuration that sets up blocks and themes similar to
a "standard" Drupal installation. If you want to start with less (which might be
preferable if a project is planning on building an entire theme from scratch), delete
all of the configuration files found in `/config`. If you do not import config, the site
will be setup with the "Stark" theme. The installation profile used can be set in the
Makefile under the `drupal-install` target, however, if you use any configuration, it's
recommended that you leave it "minimal" to avoid conflicts in the future.
* This repository uses a local.settings.php development file. It is included automatically 
if the file exists. If this code base is used in production, ensure that there 
is a deployment process in place to remove this file before going live. This could be
an outright removal or it could be something like a switch to determine your environment.

### Drush/Drupal Console

Custom implementations for drush and the Drupal Console have been created to allow 
communication with these tools inside the running instances. You can execute commands 
using `bin/drush` and `bin/drupal`. These commands are special scripts that work 
with the `drush` and `drupal` installed by Composer but from the inside of your docker 
container so that they can access your environments running resources.

Additional scripts exist in the event that you want to use XDebug with drush or Drupal
Console. To execute these, place breakpoints in your local code and run your commands
with `bin/drush-debug` or `bin/drupal-debug`. Some additional configuration in your IDE
or debugging environment may be required.

### Composer

Similar to the drush and Drupal scripts, this project provides a custom implementation
of Composer that can be executed with `bin/composer`. This is done to eliminate the
need for a local Composer (and further PHP) install as well as to ensure that the same
versions is used consistently between environments.

If installed and configured, a local Composer installation could continue to be used
to manage the environment, but any files created by `bin/composer` (including the original
system initialization), will be permission locked and might require running 
`make fix-permissions` to rectify the problem.

### Additional Tools

Lastly, this project ships with `bin/tool` and `bin/host-tool`. These are scripts that
exist to forward your input commands into docker space. Explicitly, `bin/tool` executes
any command passed to it on the running PHP container. `bin/host-tool` executes commands
passed to it in a _new_ container that has the entire project folder mounted into it.
The former is useful for debugging a live environment, the latter for manipulation while
an environment is offline. In practice, neither of these will be directly used very often
by developers.

### Production Export

#### Standard Production

In order to bring a site developed in this project to production, the entire code base
can be cloned down to the production server. Ensure the following steps are met:

1. Point the web server (apache, nginx, etc) to `webroot` in your project
    * This directory contains Drupal and everything the web server needs -- do not 
    serve the entire project directory!
2. Set environment variables or modify settings.php for the database
    * Environment variables required to work are `MYSQL_DATABASE`, `MYSQL_USER`, 
    `MYSQL_PASSWORD`, and `DRUPAL_MYSQL_HOST`
3. If necessary, remove settings.local.php
    * It is recommended that additional, programmatic logic is setup to ensure this is
     removed automatically -- leaving this file in your production environment is a 
     liability at worst and a performance hit at minimum

#### Docker Production

If your production environment is based on docker, you can prepare an image of the
current codebase:

1. `make export-prod`

In the process that follows the running of the command, docker will create new images
based on a similar base to the development images with a few exceptions:

* Composer-managed files such as vendor, core, and modules will be pulled in from
a sterile environment. This is to ensure that no hacking of these libraries accidentally
makes it into the environment. Permanent hacks to libraries managed by Composer should
be managed using Composer Patches.
* Composer installs without development dependencies. It is essential that Drupal's configuration
is expecting this. If modules, such as Devel, are tracked as a Composer development
dependency, Drupal will throw errors about not being able to find the Devel files if it
still expects, because of configuration, for the module to be there. The [Configuration
Split](https://www.drupal.org/project/config_split) module is excellent for this purpose.
* Debugging tools such as XDebug are not available

Tarball images for each of the PHP, Web, and Database containers will be saved into the
root of the project directory. These images can be deployed using standard orchestration
layers or manually if the environment is configured for it.

## Development Workflow

This environment contains most tools needed to perform day-to-day development 
activities, but some steps and make targets might be vague. These are some of the 
common use cases and how the environment can be used to satisfy them.

### Initialization

To begin work, clone the repository. Once the repository is cloned and the 
prerequisites are met (Composer and docker installed locally), a single command 
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

A more advanced target, `clean`, will run a `destroy` _plus_ remove any created
or cached docker images. This effectively reverts your environment to a pre-development
environment state.

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
used for libraries as well as other PHP packages. By default, installed Composer 
packages install to the `/vendor` directory. Special packages (such as Drupal 
modules) may be installed to different directories.

This is an example adding [PHP Code Sniffer](https://packagist.org/packages/squizlabs/php_codesniffer) 
to your environment.

1. `bin/composer require squizlabs/php_codesniffer`
2. `git add composer.json composer.lock`
3. `git commit -m "Added PHP_CodeSniffer to environment."`

Note that the files downloaded by Composer and placed in `/vendor` are not 
tracked in your SCM and shouldn't be added manually. The composer.json and composer.lock 
files contain enough information for other environments to obtain the exact same code the 
next time their environments are built.

### Installing A Drupal Module

Composer is also used to install Drupal modules, themes, profiles, and 
libraries. The end directory for these packages varies depending on the type 
of package being required, however, like the `/vendor` directory, these 
directories are ignored in git and should not be added to your SCM.

This is an example adding [Webform](https://www.drupal.org/project/webform) to 
your environment and activating it.

1. `bin/composer require drupal/webform`
2. `bin/drush en webform`
3. `make config-export`
4. `git add composer.json composer.lock config/core.extension.yml`
5. `git commit -m "Added Drupal Webform to environment."`

These steps are similar to a normal Composer package, but the environment needs 
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
command after creation to rectify this and edit locally.

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
configuration commits simple and often. Be mindful the quantity of files can 
become very large very quickly.

Note that all configuration that does not match what is in the `/config` 
directory will be exported. This does not mean that all of it needs to be 
committed. In the example above, the `/config` directory is blanket added to git 
but this might not always be the case. Take care to only commit what is 
necessary to the work at hand.

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

If an environment has no uncommitted configuration or code changes in it, the 
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
commands, `make update` should be used _much_ more often.

### Running Behat Tests

Some tests require the use of a browser, real or headless. This environment
has Behat installed as a dependency and runs Selenium with drivers for Chrome
and Firefox preconfigured. These browsers are run in a self-contained docker
environment.

Feature yaml files can be placed in tests/behat/features. These features can
be run with `make behat`. By default, the Firefox browser is used. If Chrome
is preferred, call the handler script with the option: `bin/behat -p chrome`.

For debugging purposes, the processes around bring the testing environment up,
running the tests, and finally bringing the environment down are isolated into
separate make targets:

* `make behat-start`
* `make behat-run`
* `make behat-stop`

While the environment is running, it can be accessed via a VNC connection:

* [Firefox (localhost:5900)](vnc://localhost:5900)
* [Chrome (localhost:5901)](vnc://localhost:5901)

The password to the VNC environment is "secret". 

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

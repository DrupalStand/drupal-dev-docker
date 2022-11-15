# Grab environment information (OSX vs Linux)
UNAME := $(shell uname)
DOCKER_COMPOSE_FILE := docker-compose.yml
WSL := $(shell grep -q -i microsoft /proc/version && echo 1 || echo 0)
DOCKER_HOST_IP := $(shell ping host.docker.internal -W 0 -c 1 | grep -Eo '([0-9]+\.){3}[0-9]+' | tail -1)

export PROJECT := $(shell basename $(CURDIR) | tr '[:upper:]' '[:lower:]')
export IMAGE_MAINTAINER := $(shell grep '^IMAGE_MAINTAINER' ./environment | sed 's/^.*=//g' | tr '[:upper:]' '[:lower:]')
LABLE_BASE := ${IMAGE_MAINTAINER}/${PROJECT}

ifeq ($(DOCKER_HOST_IP), )
  export DOCKER_INTERNAL_IP=$(shell ip route | grep -E '(default|docker0)' | grep -Eo '([0-9]+\.){3}[0-9]+' | tail -1)
else
  export DOCKER_INTERNAL_IP=$(DOCKER_HOST_IP)
endif

ifeq ($(UNAME), Linux)
  ifeq ($(WSL), 0)
    DOCKER_COMPOSE_FILE += -f docker-compose.linux.yml
  endif
endif

INCLUDE_MAKEFILES=

# This should always be the first target so that we know running make without any
# arguments is going to be nondestructive. The @ is to silence the normal make
# behavior of echo'ing commands before running them.
help: # Show this help
	@echo "Please specify a target. See README for information about targets."
	@echo ""
	@cat Makefile ${INCLUDE_MAKEFILES} | \
	   grep -E '^[a-zA-Z_-]+:.*?#' | \
	   sed 's/:.*# /,/' | \
	   sort | \
	   sed 's/^/\o033[32m/' | # Start Green color on first column \
	   sed 's/,/\o033[0m,/' | # End Green color on first colum \
	   column -c "Target,Description" -t -s ","
	@echo ""
	@echo "Example Usage"
	@echo "make <target>"
	@echo "make clear-cache"

include ${INCLUDE_MAKEFILES}

##
# Core commands
# The following commands are the basis of the development infrastructure.
##
init: composer-install docker-rebuild wait-healthy init-drupal docker-status # Build environment

# Use this if you would like a target to require that the project containers
# are running before executing the target contents. Note this doesn't test if
# the containers are healthy.
docker-running:
	@docker inspect -f '{{.State.Running}}' ${PROJECT}-{db,php,web} &>/dev/null \
	  || (echo "Containers are not running" && exit 1)

wait-healthy:
	@echo "Wait for all containers to become healthy"
	@python $(CURDIR)/scripts/docker-compose-wait.py

docker-rebuild: # Update docker images if there have been changes to Dockerfiles
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

status: docker-status # Alias to docker-status
docker-status: # Display status of containers related to this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

start: docker-start # Alias to docker-start
docker-start: # Start containers for this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

stop: docker-stop # Alias to docker-stop
docker-stop: # Stop containers for this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} down

restart: docker-restart # Alias to docker-restart
docker-restart: docker-stop docker-start # Restart containers for this project

composer-install: # Installs Composer packages from composer.lock file
	$(CURDIR)/bin/composer install \
		  --ignore-platform-reqs \
		  --no-interaction \
		  --no-progress

composer-update: # Update all composer managed libraries
	$(CURDIR)/bin/composer update \
		--ignore-platform-reqs

composer-update-lock:
	$(CURDIR)/bin/composer update \
		--lock

drupal-upgrade: # Update Drupal Core
	$(CURDIR)/bin/composer update drupal/core \
		--with-all-dependencies \
		--ignore-platform-reqs

destroy: files-purge composer-purge docker-destroy # Take down and remove all data related to this project's current state

files-purge:
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/sites/default/files/*

docker-destroy:
	docker-compose -f ${DOCKER_COMPOSE_FILE} down -v

composer-purge:
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/core/*
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/libraries/*
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/modules/contrib/*
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/profiles/contrib/*
	$(CURDIR)/bin/host-tool \
		rm -rf webroot/themes/contrib/*
	$(CURDIR)/bin/host-tool \
		rm -rf vendor/*

clean: destroy # Removes all artifacts built via make or docker
	@echo "Removing production tarball"
	rm $(CURDIR)/${PROJECT}-prod.tar || true
	@echo "Removing this projects docker images"
	docker rmi ${LABLE_BASE}{,-prod}-{db,php,web}:latest \
	  || true
	@echo "Remove drupalstand's shared composer cache"
	docker volume rm composer-cache || true

rebuild: destroy init # Destroy and rebuild the environment

fix-permissions: # Fix issues with permissions by taking ownership of all files
	$(CURDIR)/bin/host-tool \
		chown $(shell id -u) ./
	$(CURDIR)/bin/host-tool \
		chmod u=rwx,g=rwxs,o=rx ./
	$(CURDIR)/bin/host-tool \
		find ./ -not -path "webroot/sites/default/files*" -exec chown $(shell id -u) {} \;
	$(CURDIR)/bin/host-tool \
		find ./ -not -path "webroot/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;
	$(CURDIR)/bin/host-tool \
		find ./ -type d -not -path "webroot/sites/default/files*" -exec chmod g+s {} \;
	$(CURDIR)/bin/host-tool \
		chmod -R u=rwx,g=rwxs,o=rwx ./webroot/sites/default/files

export-prod: # Export production tarball
	docker build \
	  --target php-prod \
	  -t ${LABLE_BASE}-prod-php:latest \
	  -f docker-src/cms/Dockerfile .
	docker build \
	  --target web-prod \
	  -t ${LABLE_BASE}-prod-web:latest \
	  -f docker-src/cms/Dockerfile .
	docker build \
	  -t ${LABLE_BASE}-prod-db:latest \
	  docker-src/db
	docker save \
	  -o ${PROJECT}-prod.tar \
	  ${LABLE_BASE}-{php,web,db}:latest \
	  memcached:1.5-alpine

##
# Drupal specific commands
# The following commands are used to strap and control Drupal.
##
init-drupal: drupal-install clear-cache

update: docker-stop composer-install docker-rebuild wait-healthy clear-cache config-import updb clear-cache # Run the 'rebuild' task then import configuration and clear Drupal's cache.

safe-update: docker-stop composer-install docker-rebuild wait-healthy clear-cache updb clear-cache # Update without importing config.

drupal-install: docker-running
	$(CURDIR)/bin/drush \
	  site:install minimal \
	    -vv \
	    --yes \
	    --account-name=admin \
	    --account-pass=admin \
	    --existing-config \
	    install_configure_form.enable_update_status_module=NULL \
	    install_configure_form.enable_update_status_emails=NULL
	$(CURDIR)/bin/tool chmod 777 /var/www/webroot/sites/default/files

updb:
	$(CURDIR)/bin/drush updatedb --yes

config-init: docker-running
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Processing setting uuid..."; \
		cat ./config/system.site.yml | \
		grep uuid | tail -c +7 | head -c 36 | \
		$(CURDIR)/bin/drush config:set -y system.site uuid - ;\
	else \
		echo "Config is empty. Skipping uuid init..."; \
	fi;

config-import: docker-running
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Importing config..."; \
		$(CURDIR)/bin/drush config:import --yes ;\
		$(CURDIR)/bin/drush config:import --yes ;\
	else \
		echo "Config is empty. Skipping import..."; \
	fi;

config-export: docker-running
	$(CURDIR)/bin/drush config:export --yes

config-validate: docker-running
	$(CURDIR)/bin/drush config:status

config-refresh: config-init config-import

clear-cache: docker-running # Clear Drupal cache
	$(CURDIR)/bin/drush cache:rebuild

##
# Development commands
# The following commands are used for development purposes.
##
lint: # Check code for formatting or syntax errors
	$(CURDIR)/bin/tool parallel-lint \
	  -e php,module,inc,install,test,profile,theme \
	  /var/www/webroot/modules/custom \
	  /var/www/webroot/themes/custom

sniff: # Drupal standards checking
	$(CURDIR)/bin/tool phpcs --config-set installed_paths ../../../vendor/drupal/coder/coder_sniffer
	$(CURDIR)/bin/tool phpcs -n --standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,test,profile,theme,info \
		/var/www/webroot/modules/custom \
		/var/www/webroot/themes/custom

code-test: lint sniff # Executes PHP linting and Drupal standards checking

code-fix: # Fix minor errors using Drupal standards
	$(CURDIR)/bin/tool phpcs --config-set installed_paths ../../../vendor/drupal/coder/coder_sniffer
	-$(CURDIR)/bin/tool phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info /var/www/webroot/modules/custom
	-$(CURDIR)/bin/tool phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info /var/www/webroot/themes/custom

behat-start:
	docker-compose -f tests/behat/docker-compose.yml up -d

behat-run:
	-./bin/behat

behat-stop:
	docker-compose -f tests/behat/docker-compose.yml down -v

behat: behat-start behat-run behat-stop

##
# Removes "No rule to make target" message which allows us to pass an argument
# without having to specify the name when running the make command.
#
# https://stackoverflow.com/questions/6273608/how-to-pass-argument-to-makefile-from-command-line/6273809
#
# Please see the shell or logs targets below for an example.
##
%:
	@:

shell: # This command will open a shell in the specified container as root. Usage: make shell <container type>
	docker exec -it --user root ${PROJECT}-$(filter-out $@,$(MAKECMDGOALS)) /bin/sh

logs: # This command will watch the logs in the specified container. Usage: make logs <container type>
	docker logs -f ${PROJECT}-$(filter-out $@,$(MAKECMDGOALS)) 

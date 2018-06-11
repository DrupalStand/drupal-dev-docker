include .env

# Grab environment information (OSX vs Linux)
UNAME := $(shell uname)
DOCKER_COMPOSE_FILE := docker-compose.yml

ifeq ($(PROJECT),)
  export PROJECT := $(shell basename $(CURDIR))
endif

ifeq ($(UNAME), Linux)
  DOCKER_COMPOSE_FILE += -f docker-compose.linux.yml
endif

INCLUDE_MAKEFILES=

ifeq (${ENV}, dev)
  DOCKER_COMPOSE_FILE +=  -f docker-compose.dev.yml
  INCLUDE_MAKEFILES += Makefile.dev
endif

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
	   column -N "Target,Description" -t -s ","
	@echo ""
	@echo "Example Usage"
	@echo "make <target>"
	@echo "make clear-cache"

include ${INCLUDE_MAKEFILES}

## removes "No rule to make target" message which allows us to pass an argument
## without having to specify the name when running the make command
## https://stackoverflow.com/questions/6273608/how-to-pass-argument-to-makefile-from-command-line/6273809
##
## Please see the drush target below for an example
%:
	@:




init: docker-start wait-healthy init-drupal docker-status # Build environment

safe-update: docker-stop docker-rebuild wait-healthy clear-cache # update without importing config

docker-running:
	@docker inspect -f '{{.State.Running}}' ${PROJECT}-${ENV}-{db,php,web} &>/dev/null

wait-healthy:
	@echo "Wait for all containers to become healthy"
	@python $(CURDIR)/bin/docker-compose-wait.py

docker-rebuild: docker-stop # Update docker images if there has been changes to Dockerfiles
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

docker-status: # Display status of containers related to this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

docker-start: # Start containers for this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

docker-stop: # Stop containers for this project
	docker-compose -f ${DOCKER_COMPOSE_FILE} down

docker-restart: docker-stop docker-start # Restart containers for this project

drush: # Forwards to drush inside php container
	docker exec -i ${PROJECT}-${ENV}-php drush $(filter-out $@,$(MAKECMDGOALS))

composer: # Runs composer
	docker run \
	  --rm \
	  -v $(CURDIR)/composer.json:/app/composer.json \
	  -v $(CURDIR)/composer.lock:/app/composer.lock \
	  composer $(filter-out $@,$(MAKECMDGOALS))
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

composer-install: # Installs composer packages from composer.lock file
	docker exec -i -w "/var/www" ${PROJECT}-${ENV}-php \
          composer install --dev --ignore-platform-reqs --no-interaction --no-progress


composer-update: # Update lock file from composer.json and rebuild images
	docker run \
	  --rm \
	  -v $(CURDIR)/composer.json:/app/composer.json \
	  -v $(CURDIR)/composer.lock:/app/composer.lock \
	  composer update --lock --no-scripts --no-autoloader
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

drupal-upgrade: # update drupal core
	docker run \
	  --rm \
	  -v $(CURDIR)/composer.json:/app/composer.json \
	  -v $(CURDIR)/composer.lock:/app/composer.lock \
	  -v $(CURDIR)/scripts:/app/scripts \
	  composer update drupal/core --lock --with-dependencies
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

destroy: # take town and remove all data related to this project's current state
	docker-compose -f ${DOCKER_COMPOSE_FILE} down -v

clean: destroy # Removes all artifacts built via make or docker
	@echo "Removing docker images"
	rm $(CURDIR)/${PROJECT}-prod.tar || true
	@echo "Removing docker images"
	docker rmi ${IMAGE_MAINTAINER}/${PROJECT}-{dev,prod}-{db,php,web}:latest \
	  || true
	#docker rmi \
	#  memcached:1.5-alpine \
	#  alpine:latest \
	#  composer:latest \
	#  php:7.1-fpm-alpine \
	#  nginx:stable-alpine \
	#  || true

rebuild: destroy init # Destroy and Init the environment

fix-permissions: # Permissions all buggered up? Run this
	sudo chown $(USER) ./
	sudo chmod u=rwx,g=rwxs,o=rx ./
	sudo find ./ -not -path "drupal/sites/default/files*" -exec chown $(USER) {} \;
	sudo find ./ -not -path "drupal/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;                                                                                   
	sudo find ./ -type d -not -path "drupal/sites/default/files*" -exec chmod g+s {} \;                                                                                        
	sudo chmod -R u=rwx,g=rwxs,o=rwx ./drupal/sites/default/files;

export-prod: # Export prod tar ball
	@export ENV=prod
	docker build --target php-prod \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-php:latest \
	  -f docker-src/cms/Dockerfile .
	docker build --target web-prod \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-web:latest \
	  -f docker-src/cms/Dockerfile .
	docker build \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-db:latest \
	  docker-src/db
	docker save -o ${PROJECT}-prod.tar \
	  ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-{php,web,db}:latest \
	  memcached:1.5-alpine

init-drupal: drupal-install config-init config-import clear-cache

update: docker-stop docker-rebuild config-import clear-cache # Run the 'rebuild' task then import configuration and clear Drupal's cache

drupal-install: docker-running
	docker exec -i ${PROJECT}-${ENV}-php drush \
	  --root=/var/www/drupal site-install minimal -vv --yes \
	  --account-name=admin \
	  --account-pass=admin \
	  --site-name="Drupal Dev Docker" \
	  install_configure_form.enable_update_status_module=NULL \
	  install_configure_form.enable_update_status_emails=NULL

config-init: docker-running
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Processing setting uuid..."; \
		cat ./config/system.site.yml | \
		grep uuid | tail -c +7 | head -c 36 | \
		docker exec -i ${PROJECT}-${ENV}-php drush config-set -y system.site uuid - ;\
	else \
		echo "Config is empty. Skipping uuid init..."; \
	fi;

config-import: docker-running
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Importing config..."; \
		docker exec -i ${PROJECT}-${ENV}-php drush config-import sync --yes ;\
	else \
		echo "Config is empty. Skipping import..."; \
	fi;

config-export: docker-running
	docker exec -i ${PROJECT}-${ENV}-php drush config-export sync --yes

config-validate: docker-running
	docker exec -i ${PROJECT}-${ENV}-php drush config-export sync --no

config-refresh: config-running



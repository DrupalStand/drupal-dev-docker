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

ifeq (${DRUPAL_VERSION}, 7)
  INCLUDE_MAKEFILES += Makefile.d7
endif

ifeq (${DRUPAL_VERSION}, 8)
  INCLUDE_MAKEFILES += Makefile.d8
endif


# This should always be the first target so that we know running make without any
# arguments is going to be nondestructive. The @ is to silence the normal make
# behavior of echo'ing commands before running them.
help:
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

init: docker-start ready init-drupal docker-status # Build environment

init-drupal: drupal-install config-init config-import clear-cache

update: docker-stop docker-rebuild ready config-import clear-cache

safe-update: docker-stop docker-rebuild ready clear-cache

docker-rebuild:
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps
	@sleep 10

docker-status:
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

docker-start: # Start Docker
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps
	@sleep 15

docker-stop:
	docker-compose -f ${DOCKER_COMPOSE_FILE} down

docker-restart: docker-stop docker-start

drush: # drush
	docker exec -i php-${PROJECT} drush $(filter-out $@,$(MAKECMDGOALS))

composer:
	docker run \
	  --rm \
	  -v $(CURDIR):/app/ \
	  composer $(filter-out $@,$(MAKECMDGOALS))
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

composer-update:
	docker run \
	  --rm \
	  -v $(CURDIR)/composer-d${DRUPAL_VERSION}.json:/app/composer.json \
	  -v $(CURDIR)/composer-d${DRUPAL_VERSION}.lock:/app/composer.lock \
	  -v $(CURDIR)/scripts:/app/scripts \
	  composer update --lock
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

drupal-upgrade:
	docker run \
	  --rm \
	  -v $(CURDIR)/composer-d${DRUPAL_VERSION}.json:/app/composer.json \
	  -v $(CURDIR)/composer-d${DRUPAL_VERSION}.lock:/app/composer.lock \
	  -v $(CURDIR)/scripts:/app/scripts \
	  composer update drupal/core --lock --with-dependencies
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

drupal-install:
	docker exec -i php-${PROJECT} drush \
	  --root=/var/www/drupal site-install minimal -vv --yes \
	  --account-name=admin \
	  --account-pass=admin \
	  --site-name="Drupal Dev Docker" \
	  install_configure_form.enable_update_status_module=NULL \
	  install_configure_form.enable_update_status_emails=NULL

clear-cache:
	docker exec -i php-${PROJECT} drush cr

destroy:
	docker-compose -f ${DOCKER_COMPOSE_FILE} down -v

rebuild: destroy init

ready:
	@echo "Waiting for files to sync between host and Docker...";
#	@bash ./docker-src/cms/ready.sh;

fix-permissions:
	sudo chown $(USER) ./
	sudo chmod u=rwx,g=rwxs,o=rx ./
	sudo find ./ -exec chown $(USER) {} \;
	sudo find ./ -exec chmod u=rwX,g=rwX,o=rX {} \;
	sudo find ./ -type d -exec chmod g+s {} \;

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
	  ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-php:latest \
	  ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-web:latest \
	  ${IMAGE_MAINTAINER}/${PROJECT}-${ENV}-db:latest \
	  memcached:1.5-alpine


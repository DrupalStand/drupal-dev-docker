

# Grab environment information (OSX vs Linux)
UNAME := $(shell uname)
DOCKER_COMPOSE_FILE := docker-compose.yml

export PROJECT := $(shell basename $(CURDIR))
export IMAGE_MAINTAINER := $(shell grep '^IMAGE_MAINTAINER' ./environment | sed 's/^.*=//g')

ifeq ($(UNAME), Linux)
  DOCKER_COMPOSE_FILE += -f docker-compose.linux.yml
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




init: docker-start wait-healthy composer-install init-drupal docker-status # Build environment

safe-update: docker-stop docker-rebuild wait-healthy clear-cache # update without importing config

docker-running:
	@docker inspect -f '{{.State.Running}}' ${PROJECT}-{db,php,web} &>/dev/null

wait-healthy:
	@echo "Wait for all containers to become healthy"
	@python $(CURDIR)/bin/docker-compose-wait.py

docker-rebuild: docker-stop # Update docker images if there has been changes to Dockerfiles
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

docker-restart: docker-stop docker-start # Restart containers for this project

drush: # Forwards to drush inside php container
	docker exec -i ${PROJECT}-php drush $(filter-out $@,$(MAKECMDGOALS))

composer: # Runs composer
	docker run \
	  --rm \
	  -v $(CURDIR)/composer.json:/app/composer.json \
	  -v $(CURDIR)/composer.lock:/app/composer.lock \
	  composer $(filter-out $@,$(MAKECMDGOALS))
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build

composer-install: vendor # Installs composer packages from composer.lock file
	docker exec -i -w "/var/www" ${PROJECT}-php \
          composer install --ignore-platform-reqs --no-interaction --no-progress


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

vendor:
	mkdir $(CURDIR)/vendor

destroy: # take town and remove all data related to this project's current state
	docker-compose -f ${DOCKER_COMPOSE_FILE} down -v

clean: remove-artifacts destroy # Removes all artifacts built via make or docker
	@echo "Remove vendor folder"
	rmdir $(CURDIR)/vendor || true
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

remove-artifacts:
	docker exec -i ${PROJECT}-php rm -rf /var/www/vendor/*


rebuild: destroy init # Destroy and Init the environment

fix-permissions: # Permissions all buggered up? Run this
	sudo chown $(USER) ./
	sudo chmod u=rwx,g=rwxs,o=rx ./
	sudo find ./ -not -path "drupal/sites/default/files*" -exec chown $(USER) {} \;
	sudo find ./ -not -path "drupal/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;                                                                                   
	sudo find ./ -type d -not -path "drupal/sites/default/files*" -exec chmod g+s {} \;                                                                                        
	sudo chmod -R u=rwx,g=rwxs,o=rwx ./drupal/sites/default/files;

export-prod: # Export prod tar ball
	docker build --target php-prod \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-prod-php:latest \
	  -f docker-src/cms/Dockerfile .
	docker build --target web-prod \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-prod-web:latest \
	  -f docker-src/cms/Dockerfile .
	docker build \
	  -t ${IMAGE_MAINTAINER}/${PROJECT}-prod-db:latest \
	  docker-src/db
	docker save -o ${PROJECT}-prod.tar \
	  ${IMAGE_MAINTAINER}/${PROJECT}-prod-{php,web,db}:latest \
	  memcached:1.5-alpine

##
# Drupal specific commands
# The following commands woudl change based on the version of drupal
##
init-drupal: drupal-install config-init config-import clear-cache

update: docker-stop docker-rebuild config-import clear-cache # Run the 'rebuild' task then import configuration and clear Drupal's cache

drupal-install: docker-running
	docker exec -i ${PROJECT}-php drush \
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
		docker exec -i ${PROJECT}-php drush config-set -y system.site uuid - ;\
	else \
		echo "Config is empty. Skipping uuid init..."; \
	fi;

config-import: docker-running
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Importing config..."; \
		docker exec -i ${PROJECT}-php drush config-import sync --yes ;\
	else \
		echo "Config is empty. Skipping import..."; \
	fi;

config-export: docker-running
	docker exec -i ${PROJECT}-php drush config-export sync --yes

config-validate: docker-running
	docker exec -i ${PROJECT}-php drush config-export sync --no

config-refresh: config-running

##
# DEVELOPMENT TOOLS
##

lint: # Check code for formatting or syntax errors
	docker exec -i ${PROJECT}-php parallel-lint \
	  -e php,module,inc,install,test,profile,theme \
	  /var/www/drupal/modules/custom \
	  /var/www/drupal/themes/custom

sniff: # Description needed
	./vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
	./vendor/bin/phpcs -n --standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,test,profile,theme,info \
		--ignore=*/node_modules/* web/modules/custom web/themes/custom

code-test: lint sniff # Description needed

code-fix: # Description needed
	vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
	-vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info web/modules/custom
	-vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info --ignore=*/node_modules/* web/themes/custom
	#
shell: # This command will open a shell in the specified container as root. Usage: make shell <container type>
	docker exec -it --user root ${PROJECT}-$(filter-out $@,$(MAKECMDGOALS)) /bin/sh

exec: # This command will open a run a command in the specified container as root. Usage: make exec <container type> <cmd>
	docker exec -it --user root ${PROJECT}-$(filter-out $@,$(MAKECMDGOALS))

logs: # This command will watch the logs in the specified container. Usage: make logs <container type>
	docker logs -f ${PROJECT}-$(filter-out $@,$(MAKECMDGOALS)) 

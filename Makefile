# Grab environment information (OSX vs Linux)
UNAME := $(shell uname)
DOCKER_COMPOSE_FILE := docker-compose.yml
ifeq ($(UNAME), Linux)
	DOCKER_COMPOSE_FILE := docker-compose.linux.yml
endif

# This should always be the first target so that we know running make without any
# arguments is going to be nondestructive. The @ is to silence the normal make
# behavior of echo'ing commands before running them.
faketarget:
	@echo "Please specify a target. See README for available targets."

init: salt docker-start ready composer-install init-drupal docker-status

init-drupal: drupal-install config-init config-import clear-cache

update: docker-stop composer-install docker-rebuild ready config-import clear-cache

safe-update: docker-stop composer-install docker-rebuild ready clear-cache

docker-rebuild:
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d --build
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps
	@sleep 10

docker-status:
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps

docker-start:
	docker-compose -f ${DOCKER_COMPOSE_FILE} up -d
	docker-compose -f ${DOCKER_COMPOSE_FILE} ps
	@sleep 10

docker-stop:
	docker-compose -f ${DOCKER_COMPOSE_FILE} down

composer-install:
	./bin/composer install --ignore-platform-reqs --no-interaction --no-progress

composer-update:
	./bin/composer update --ignore-platform-reqs --no-interaction --no-progress --prefer-dist

drupal-upgrade:
	./bin/composer update drupal/core --with-dependencies

drupal-install:
	./bin/drush --root=/var/www/web site-install minimal -vv --account-name=admin --account-pass=admin --yes \
		install_configure_form.enable_update_status_module=NULL \
		install_configure_form.enable_update_status_emails=NULL

config-init:
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Processing setting uuid..."; \
		cat ./config/system.site.yml | \
		grep uuid | tail -c +7 | head -c 36 | \
		docker exec -i cms sh -c "/var/www/vendor/bin/drush \
		--root=/var/www/web config-set -y system.site uuid - ";\
	else \
		echo "Config is empty. Skipping uuid init..."; \
	fi;

config-import:
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Importing config..."; \
		./bin/drush config-import sync --yes ;\
		./bin/drush config-import sync --yes ;\
	else \
		echo "Config is empty. Skipping import..."; \
	fi;

config-export:
	./bin/drush config-export sync --yes

config-validate:
	./bin/drush config-export sync --no

config-refresh: config-init config-import

salt:
	cat /dev/urandom | LC_CTYPE=C tr -dc 'a-zA-Z0-9' | fold -w 64 | head -n 1 > salt.txt

clear-cache:
	./bin/drush cr

destroy:
	docker-compose -f ${DOCKER_COMPOSE_FILE} down -v
	sudo rm -rf ./web/sites/default/files/*
	sudo rm -rf ./web/core/*
	sudo rm -rf ./web/libraries/*
	sudo rm -rf ./web/modules/contrib/*
	sudo rm -rf ./web/profiles/contrib/*
	sudo rm -rf ./web/themes/contrib/*
	sudo rm -rf ./drush/contrib/*
	sudo rm -rf ./vendor/*

rebuild: destroy init

ready:
	@echo "Waiting for files to sync between host and Docker...";
	@bash ./docker-src/cms/ready.sh;

lint:
	./vendor/bin/parallel-lint -e php,module,inc,install,test,profile,theme ./web/modules/custom ./web/themes/custom

sniff:
	./vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
	./vendor/bin/phpcs -n --standard=Drupal,DrupalPractice \
		--extensions=php,module,inc,install,test,profile,theme,info \
		--ignore=*/node_modules/* web/modules/custom web/themes/custom

code-test: lint sniff

code-fix:
	vendor/bin/phpcs --config-set installed_paths vendor/drupal/coder/coder_sniffer
	-vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info web/modules/custom
	-vendor/bin/phpcbf --standard=Drupal --extensions=php,module,inc,install,test,profile,theme,info --ignore=*/node_modules/* web/themes/custom

fix-permissions:
	sudo chown $(USER) ./
	sudo chmod u=rwx,g=rwxs,o=rx ./
	sudo find ./ -not -path "web/sites/default/files*" -exec chown $(USER) {} \;
	sudo find ./ -not -path "web/sites/default/files*" -exec chmod u=rwX,g=rwX,o=rX {} \;
	sudo find ./ -type d -not -path "web/sites/default/files*" -exec chmod g+s {} \;
	sudo chmod -R u=rwx,g=rwxs,o=rwx ./web/sites/default/files;

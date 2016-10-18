# Default Drush arguments
DRUSH_ARGS := --root=/var/www/web

# This should always be the first target so that we know running make without any
# arguments is going to be nondestructive. The @ is to silence the normal make
# behavior of echo'ing commands before running them.
faketarget:
	@echo "Please specify a target. See README for available targets."

init: salt composer-install docker-start init-drupal docker-status

init-drupal: drupal-install config-init config-import clear-cache

docker-rebuild:
	docker-compose build

docker-status:
	docker-compose ps

docker-start:
	docker-compose up -d
	docker-compose ps
	@sleep 10

docker-stop:
	docker-compose down

composer-install:
	composer install --ignore-platform-reqs --no-interaction --no-progress

composer-update:
	composer update --ignore-platform-reqs --no-interaction --no-progress --prefer-dist

drupal-upgrade:
	composer update drupal/core --with-dependencies

drupal-install:
	-./bin/drush $(DRUSH_ARGS) site-install minimal -vv --account-name=admin --account-pass=admin --yes

config-init:
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Processing setting uuid..."; \
		cat ./config/system.site.yml | \
		grep uuid | tail -c +7 | head -c 36 | \
		docker exec -i cms /var/www/vendor/bin/drush $(DRUSH_ARGS) \
		config-set -y system.site uuid - ;\
	else \
		echo "Config is empty. Skipping uuid init..."; \
	fi;

config-import:
	@if [ -e ./config/system.site.yml ]; then \
		echo "Config found. Importing config..."; \
		./bin/drush $(DRUSH_ARGS) config-import sync --yes ;\
	else \
		echo "Config is empty. Skipping import..."; \
	fi;

config-export:
	-./bin/drush $(DRUSH_ARGS) config-export sync --yes

config-validate:
	-./bin/drush $(DRUSH_ARGS) config-export sync --no

config-refresh: config-init config-import

salt:
	cat /dev/urandom | LC_CTYPE=C tr -dc 'a-zA-Z0-9' | fold -w 64 | head -n 1 > salt.txt

clear-cache:
	-./bin/drush cr

destroy:
	docker-compose down -v
	rm -rf ./web/sites/default/files/*

rebuild: destroy init

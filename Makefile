#!make
DEPENDENCIES=""
BRANCH_NAME=$(shell git rev-parse --abbrev-ref HEAD)
PWD=$(shell pwd)
include .env
export $(shell sed 's/=.*//' .env)

# Start container and build Drupal 10 locally
build-local-10:
	docker run --rm --name drupalci_${PROJECT_NAME} \
	    -v ${PWD}/:/var/www/html/web/modules/contrib/${PROJECT_NAME} \
	    -v ${PWD}/../modules/:/var/www/html/web/modules \
	    -v ${PWD}/../vendor/:/var/www/vendor \
	    -v ${PWD}/../artifacts:/artifacts \
	    -p ${PROJECT_PORT}:80 \
	    -d marcelovani/drupalci:10-apache-interactive
	make build-local

build-local:
	docker exec -i drupalci_${PROJECT_NAME} bash -c "composer require ${DEPENDENCIES}"

install-local:
	docker exec -i drupalci_${PROJECT_NAME} bash -c "sudo -u www-data php web/core/scripts/drupal install standard"

reinstall-local:
	docker exec -it drupalci_paragraphs_inline_entity_form bash -c "rm -rf web/sites/default/files";
	docker exec -it drupalci_paragraphs_inline_entity_form bash -c "rm -rf web/sites/default/settings.php;";
	docker exec -it drupalci_paragraphs_inline_entity_form bash -c "chmod 777 web/sites/default;";
	make install-local

# Test local build
test-local:
	docker exec -it drupalci_${PROJECT_NAME} bash -c '\
	    sudo -u www-data php web/core/scripts/run-tests.sh \
	    --php /usr/local/bin/php \
	    --verbose \
	    --keep-results \
	    --color \
	    --concurrency "32" \
	    --repeat "1" \
	    --types "Simpletest,PHPUnit-Unit,PHPUnit-Kernel,PHPUnit-Functional" \
	    --sqlite sites/default/files/.ht.sqlite \
	    --url http://localhost \
	    --directory "modules/contrib/${PROJECT_NAME}"'

# Test in non-interactive mode
test-10:
	docker run --name drupalci_${PROJECT_NAME} \
	    -v ~/artifacts:/artifacts \
	    --rm marcelovani/drupalci:10-apache \
	    --project ${PROJECT_NAME} \
	    --version dev-1.x \
	    --dependencies ${DEPENDENCIES}

copy-results:
	docker exec -it drupalci_${PROJECT_NAME} bash -c 'rm -rf /artifacts/*; cp -a /var/www/html/web/sites/simpletest /artifacts'

open:
	open "http://$(PROJECT_BASE_URL):${PROJECT_PORT}"

stop:
	docker stop drupalci_${PROJECT_NAME}

stop-all-containers:
	ids=$$(docker ps -a -q) && if [ "$${ids}" != "" ]; then docker stop $${ids}; fi

in:
	docker exec -it drupalci_${PROJECT_NAME} bash

logs:
	docker exec -t drupalci_${PROJECT_NAME} bash -c "tail -f /var/log/messages/*.log"

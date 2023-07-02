ENV?= dev
BRANCH?= dev

#
# DOCKER VAR
#
DOCKER_COMPOSE?= docker-compose
ifeq ($(OS), Windows_NT)
    EXEC?= winpty $(DOCKER_COMPOSE) exec
else
    EXEC?= $(DOCKER_COMPOSE) exec
endif

PHP?= $(EXEC) php
COMPOSER?= $(PHP) composer
CONSOLE?= $(PHP) php bin/console
PHPUNIT?= $(PHP) php bin/phpunit

ifeq ($(SKIP_DOCKER),true)
	PHP= php
	COMPOSER= composer
	CONSOLE= $(PHP) bin/console
endif

help:  ## Display this help
	@awk 'BEGIN {FS = ":.*##"; printf "\nUsage:\n  make \033[36m\033[0m\n"} /^[a-zA-Z_-]+:.*?##/ { printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2 } /^##@/ { printf "\n\033[1m%s\033[0m\n", substr($$0, 5) } ' $(MAKEFILE_LIST)

##@ Installation
install:  build up vendor down ## Install new project with docker

update: vendor up -logs ## update after checkout

update-force: build vendor up ## update after checkout with rebuild of docker image

##@ Docker
build: ## Build the images
	$(DOCKER_COMPOSE) build --no-cache --build-arg APP_USER_ID=$$(id -u) --build-arg APP_USER=$$(id -u -n)

up: ## Up the images
	$(DOCKER_COMPOSE) up -d --remove-orphans

down: ## Down the images
	$(DOCKER_COMPOSE) down

destroy:down ## Destroy all containers and images
	-docker rm $$(docker ps -a -q)
	-docker rmi $$(docker images -q)

## don't forget this if you dont want makefile to get files with this name
.PHONY: build up down destroy update install reload vendor

##@ Composer
vendor: composer.lock ## Install composer dependency
	$(COMPOSER) install

##@ Utility
clear: ## Clear cache symfony
	$(CONSOLE) c:c

bash-php: ## Launch PHP bash
	$(PHP) bash

server-dump: ## Launch dumper on console
	$(CONSOLE) server:dump

.PHONY: clear bash-php

##@ CI
ci: ## Launch csfixer and phpstan and javascript quality check
	$(YARN) lintfix
	$(COMPOSER) ci

.PHONY: ci phptests

##@ TEST
phptests:  ## Execute phpunit classic tests without panther tagged tests
	$(CONSOLE)  c:c --env=test
	$(PHPUNIT)

phptestsall: phptests

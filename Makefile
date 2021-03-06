include Make.config

.PHONY: php-cs-fixer
php-cs-fixer:
	$(BIN_DIR)/php-cs-fixer fix --dry-run --verbose --diff

.PHONY: psalm
psalm:
	$(BIN_DIR)/psalm --no-cache

.PHONY: phpunit
phpunit: export APP_ENV=test
phpunit:
	$(APP_BIN_DIR)/console cache:clear
	$(BIN_DIR)/simple-phpunit

.PHONY: tests
tests: php-cs-fixer psalm phpunit

.PHONY: server
server: export APP_ENV=dev
server:
	$(SYMFONY_CLI) server:stop --dir=$(APP_DIR)
	$(APP_BIN_DIR)/console cache:clear
	$(APP_BIN_DIR)/console assets:install
	$(SYMFONY_CLI) server:start --dir=$(APP_DIR) --port=8080 --no-tls
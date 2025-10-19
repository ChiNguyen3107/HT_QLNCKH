# Makefile cho Testing Framework

.PHONY: help test test-unit test-feature test-coverage test-coverage-html test-coverage-text test-ci install test-setup clean

help: ## Hiển thị help
	@echo "Available commands:"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-20s\033[0m %s\n", $$1, $$2}'

install: ## Cài đặt dependencies
	composer install

test-setup: ## Setup thư mục testing
	@mkdir -p storage/coverage
	@mkdir -p storage/logs
	@mkdir -p storage/cache
	@mkdir -p storage/sessions

test: test-setup ## Chạy tất cả tests
	vendor/bin/phpunit

test-unit: test-setup ## Chạy unit tests
	vendor/bin/phpunit tests/Unit

test-feature: test-setup ## Chạy feature tests
	vendor/bin/phpunit tests/Feature

test-coverage: test-setup ## Chạy tests với coverage report
	vendor/bin/phpunit --coverage-html storage/coverage --coverage-clover storage/coverage/clover.xml

test-coverage-text: test-setup ## Chạy tests với coverage text report
	vendor/bin/phpunit --coverage-text

test-coverage-html: test-setup ## Chạy tests với HTML coverage report
	vendor/bin/phpunit --coverage-html storage/coverage

test-ci: test-setup ## Chạy tests cho CI/CD
	vendor/bin/phpunit --coverage-clover storage/coverage/clover.xml

test-verbose: test-setup ## Chạy tests với verbose output
	vendor/bin/phpunit --verbose

test-debug: test-setup ## Chạy tests với debug mode
	vendor/bin/phpunit --debug

test-filter: ## Chạy tests với filter (usage: make test-filter FILTER=testMethodName)
	vendor/bin/phpunit --filter $(FILTER)

test-stop-on-failure: test-setup ## Chạy tests và dừng khi có lỗi
	vendor/bin/phpunit --stop-on-failure

clean: ## Xóa các file test artifacts
	rm -rf storage/coverage/*
	rm -rf storage/logs/*
	rm -rf storage/cache/*
	rm -rf .phpunit.cache

serve: ## Chạy development server
	php -S localhost:8000 -t public

composer-update: ## Cập nhật composer dependencies
	composer update

composer-install: ## Cài đặt composer dependencies
	composer install --no-dev --optimize-autoloader

security-check: ## Kiểm tra security vulnerabilities
	composer audit

code-style: ## Kiểm tra code style
	@echo "Running PHP CS Fixer..."
	@vendor/bin/php-cs-fixer fix --dry-run --diff --verbose || true
	@echo "Running PHPStan..."
	@vendor/bin/phpstan analyse app/ tests/ --level=5 || true

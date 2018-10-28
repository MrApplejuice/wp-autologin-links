
.PHONY: all test test-server-up test-test test-server-down test

all: test

test: test-server-down test-server-up test-test test-server-down

test-server-up: 
	docker-compose up -d
	sleep 8

test-test:
	bash auto-install-page.sh
	./run-tests.sh

test-server-down:
	docker-compose down


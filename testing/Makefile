
define TEST_template =
test-$(1):
	wordpress_image_name=wordpress:$(1) docker-compose -p $(1) down
	wordpress_image_name=wordpress:$(1) docker-compose -p $(1) up -d
	sleep 8
	prefix=$(1) bash auto-install-page.sh
	prefix=$(1) ./run-tests.sh
	wordpress_image_name=wordpress:$(1) docker-compose -p $(1) down

ALL_TARGETS += test-$(1)
endef

TEST_CONTAINERS = \
	4.9.8-php5.6-apache \
	4.9.8-php7.1-apache \


.PHONY: all _all debug-print

all: _all


$(foreach x,$(TEST_CONTAINERS),$(eval $(call TEST_template,$(x))))


_all: $(ALL_TARGETS)


debug-print:
	echo $(ALL_TARGETS)


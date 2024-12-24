load:
	composer update
	composer dump-autoload -o

test: vendor/bin/phpunit
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	vendor/bin/phpunit tests

test-debug: vendor/bin/phpunit
	php \
	-dxdebug.mode=debug \
	-dxdebug.start_with_request=yes \
	vendor/bin/phpunit tests

fix: vendor/bin/php-cs-fixer
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	vendor/bin/php-cs-fixer fix .

preview: bin/catpaw sandbox/preview/main.php
	php \
	-dxdebug.mode=debug \
	-dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=sandbox/preview/lib \
	--main=sandbox/preview/main.php

dev: bin/catpaw src/main.php
	php \
	-dxdebug.mode=debug \
	-dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

watch: bin/catpaw src/main.php
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php \
	--resources=src \
	--watch \
	--spawner="php -dxdebug.mode=debug -dxdebug.start_with_request=yes"

start: bin/catpaw src/main.php
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

configure:
	@printf "\
	name = out/catpaw\n\
	main = src/main.php\n\
	libraries = src/lib\n\
	environment = env.ini\n\
	match = \"/(^\.\/(\.build-cache|src|vendor|bin)\/.*)|(^\.\/(\.env|env\.ini|env\.yml))/\"\n\
	" > build.ini && printf "Build configuration file restored.\n"

clean:
	rm app.phar -f
	rm vendor -fr

build: bin/catpaw-cli
	test -f build.ini || make configure
	test -d out || mkdir out
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	-dphar.readonly=0 \
	bin/catpaw-cli \
	--build \
	--optimize
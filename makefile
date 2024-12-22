load:
	composer update
	composer dump-autoload -o

test: vendor/bin/phpunit
	php vendor/bin/phpunit tests

preview: bin/catpaw preview/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes vendor/bin/catpaw \
	--environment=env.ini \
	--libraries=preview/lib \
	--main=preview/main.php

dev: bin/catpaw src/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes vendor/bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

watch: bin/catpaw src/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes vendor/bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php \
	--resources=src \
	--watch \
	--spawner="php -dxdebug.mode=debug -dxdebug.start_with_request=yes"

start: bin/catpaw src/main.php
	php -dopcache.enable_cli=1 -dopcache.jit_buffer_size=100M vendor/bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

configure:
	@printf "\
	name = out/catpaw\n\
	main = src/main.php\n\
	libraries = src/lib\n\
	match = "/(^\.\/(\.build-cache|src|vendor|bin)\/.*)|(^\.\/(\.env|env\.ini))/"\n\
	" > build.ini && printf "Build configuration file restored.\n"

clean:
	rm app.phar -f
	rm vendor -fr

build: test bin/catpaw-cli
	php -dphar.readonly=0 bin/catpaw-cli \
	--build \
	--optimize
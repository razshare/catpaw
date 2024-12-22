load:
	composer update
	composer dump-autoload -o

test: vendor/bin/phpunit
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	vendor/bin/phpunit tests

fix: vendor/bin/php-cs-fixer
	php \
	-dxdebug.mode=off \
	vendor/bin/php-cs-fixer fix .

sandbox: bin/catpaw sandbox/main.php
	php \
	-dxdebug.mode=debug \
	-dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=sandbox/lib \
	--main=sandbox/main.php

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
	match = "/(^\.\/(\.build-cache|src|vendor|bin)\/.*)|(^\.\/(\.env|env\.ini))/"\n\
	" > build.ini && printf "Build configuration file restored.\n"

clean:
	rm app.phar -f
	rm vendor -fr

build: test bin/catpaw-cli
	php \
	-dxdebug.mode=off \
	-dxdebug.start_with_request=no \
	-dphar.readonly=0 \
	bin/catpaw-cli \
	--build \
	--optimize
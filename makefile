
install:
	composer install
	composer dump-autoload -o

update:
	composer update
	composer dump-autoload -o

sandbox: bin/catpaw projects/sandbox/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=projects/sandbox/lib \
	--main=projects/sandbox/main.php

dev: bin/catpaw src/main.php
	inotifywait \
	-e modify,create,delete_self,delete,move_self,moved_from,moved_to \
	-r -m -P --format '%e' src | \
	php -dxdebug.mode=off -dxdebug.start_with_request=no \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib,src/scripts \
	--main=src/main.php \
	--spawner="php -dxdebug.mode=debug -dxdebug.start_with_request=yes" \
	--wait

preview: bin/catpaw src/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

start: bin/catpaw src/main.php
	php -dxdebug.mode=off -dxdebug.start_with_request=no \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php

build: bin/catpaw-cli
	test -f build.ini || make configure
	test -d out || mkdir out
	php -dxdebug.mode=off -dxdebug.start_with_request=no \
	-dphar.readonly=0 \
	bin/catpaw-cli \
	--build \
	--optimize

test: vendor/bin/phpunit
	php -dxdebug.mode=off -dxdebug.start_with_request=no \
	vendor/bin/phpunit tests

clean:
	rm app.phar -f
	rm vendor -fr

configure:
	@printf "\
	name = out/catpaw\n\
	main = src/main.php\n\
	libraries = src/lib\n\
	environment = env.ini\n\
	match = \"/(^\.\/(\.build-cache|src|vendor|bin)\/.*)|(^\.\/(\.env|env\.ini|env\.yml))/\"\n\
	" > build.ini && printf "Build configuration file restored.\n"
	make install

fix: vendor/bin/php-cs-fixer
	php -dxdebug.mode=off -dxdebug.start_with_request=no \
	vendor/bin/php-cs-fixer fix .

hooks: bin/catpaw src/main.php
	php -dxdebug.mode=debug -dxdebug.start_with_request=yes \
	bin/catpaw \
	--environment=env.ini \
	--libraries=src/lib \
	--main=src/main.php \
	--install-pre-commit="make test"
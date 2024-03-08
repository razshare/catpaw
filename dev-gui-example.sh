#!/bin/bash

pushd src/lib/Gui/lib &&\
go build -o main.so -buildmode=c-shared main.go &&\
cpp -P ./main.h ./main.static.h &&\
popd || exit

php -dxdebug.mode=debug -dxdebug.start_with_request=yes -dphar.readonly=0 ./bin/start --libraries='./src/lib' --entry='./src/gui-example.php'

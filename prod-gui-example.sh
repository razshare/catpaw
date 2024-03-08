#!/bin/bash

pushd src/lib/Gui/lib &&\
go build -o main.so -buildmode=c-shared main.go &&\
cpp -P ./main.h ./main.static.h &&\
popd || exit

php -dphar.readonly=0 -dopcache.enable_cli=1 -dopcache.jit_buffer_size=100M ./bin/start --libraries='./src/lib' --entry='./src/gui-example.php'

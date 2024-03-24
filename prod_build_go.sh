#!/bin/bash

pushd src/lib/Go/lib &&\
go build -o main.so -buildmode=c-shared main.go &&\
cpp -P ./main.h ./main.static.h &&\
popd || exit

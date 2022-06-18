#!/bin/bash
PWD=$(pwd)
NAME=$(cat $PWD/name)

docker build . -t $NAME --force-rm --no-cache

cd $PWD
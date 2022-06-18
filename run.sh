#!/bin/bash
PWD=$(pwd)
NAME=$(cat $PWD/dockerName)
IMAGENAME=$(cat $PWD/name)

docker run -itd \
    -p 8080:80 \
    --rm \
    -v /$PWD:/var/www/html/ \
    --hostname $NAME \
    --name $NAME $IMAGENAME $@

exit 0

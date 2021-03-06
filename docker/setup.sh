#!/bin/bash

set -e

DIR=$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )

docker build -t paraunit_container $DIR
docker run -v $DIR/../:/home/paraunit/projects --rm -ti -u paraunit paraunit_container zsh

#!/bin/bash

### Athletica

#wget https://www.swiss-athletics.ch/athletica/de_Athletica_Pkg_72.zip


### Docker

## mysql
docker run --rm --name athletica-mysql -e MYSQL_ROOT_PASSWORD=roott -e MYSQL_DATABASE=athletica -e MYSQL_USER=athletica -e MYSQL_PASSWORD=athletica -v "$(pwd)"/src/athletica/sql/athletica.sql:/docker-entrypoint-initdb.d/athletica.sql -d mysql

## php (with apache)
docker build -t athletica-php .
docker run --rm -d --link athletica-mysql:db -p 80:80 -v "$(pwd)"/src:/var/www/html --name athletica-php athletica-php

## check logs
#docker logs athletica-mysql

## bash into running container
#docker exec -ti athletica-mysql bash

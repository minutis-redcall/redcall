#!/bin/bash

me=$0
mydir=`dirname $me`
source .env

set -e

php bin/console doctrine:database:drop --if-exists --force -n
php bin/console doctrine:database:create
php bin/console doctrine:migrations:migrate -n
php bin/console doctrine:database:import data/fixtures.sql
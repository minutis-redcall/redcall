#!/bin/bash

set -e

php bin/console doctrine:database:drop --if-exists --force -n
php bin/console doctrine:database:create

php bin/console doctrine:migration:migrate
php bin/console doctrine:database:import dev/database/fixtures.sql

#!/bin/bash

set -e

php symfony/bin/console doctrine:database:drop --if-exists --force -n
php symfony/bin/console doctrine:database:create

php symfony/bin/console doctrine:migration:migrate
php symfony/bin/console doctrine:database:import dev/database/fixtures.sql

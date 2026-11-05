#!/bin/sh
# Creates the database used by the test suite and grants it to the application user.
#
# The MySQL image runs this only when the volume is first initialised. On an existing
# volume, run it once by hand: docker compose exec mysql /docker-entrypoint-initdb.d/create-testing-database.sh
set -e

# MYSQL_PWD keeps the root password out of the process list.
MYSQL_PWD="$MYSQL_ROOT_PASSWORD" mysql -uroot <<SQL
CREATE DATABASE IF NOT EXISTS \`testing\`;
GRANT ALL PRIVILEGES ON \`testing\`.* TO '${MYSQL_USER}'@'%';
SQL

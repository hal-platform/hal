#!/bin/bash
set -eo pipefail

trap stop SIGINT SIGTERM

function log {
  local readonly msg="$@"
  >&2 echo -e "$msg"
}

function ensure_not_empty {
  local readonly arg_name="$1"
  local readonly arg_value="$2"

  if [[ -z "$arg_value" ]]; then
    log "ERROR: The value for '$arg_name' cannot be empty"
    exit 1
  fi
}

function ensure_is_installed {
  local readonly name="$1"

  if [[ ! $(command -v ${name}) ]]; then
    log "ERROR: The binary '$name' is required by this script but is not installed or in the system's PATH."
    exit 1
  fi
}

migrate_db() {
    echo "Migrating Hal database ..."
    ./vendor/bin/hal-phinx migrate
}

configure_hal() {
    local readonly defaultfile="/.env.default"
    local readonly envfile="/app/config/.env"

    local readonly root_path="/app"
    local readonly base_url=${APP_URL:-http://localhost}

    local readonly database_username="${DB_USERNAME}"
    local readonly database_password="${DB_PASSWORD}"
    local readonly database_host="${DB_HOST}"
    local readonly database_port="${DB_PORT}"
    local readonly database_name="${DB_DATABASE}"
    local readonly database_driver="pdo_${DB_DRIVER}"
    local readonly redis_host="${REDIS_HOST:-redis}"

    if [[ -f "${defaultfile}" && ! -f "${envfile}" ]]; then
        echo "Copying default env file from ${defaultfile} to ${envfile}"
        cp "${defaultfile}" "${envfile}"
    fi

    sed 's,{{root_path}},'"${root_path}"',g' -i ${envfile}
    sed 's,{{base_url}},'"${base_url}"',g' -i ${envfile}
    sed 's,{{database_username}},'"${database_username}"',g' -i ${envfile}
    sed 's,{{database_password}},'"${database_password}"',g' -i ${envfile}
    sed 's,{{database_host}},'"${database_host}"',g' -i ${envfile}
    sed 's,{{database_port}},'"${database_port}"',g' -i ${envfile}
    sed 's,{{database_name}},'"${database_name}"',g' -i ${envfile}
    sed 's,{{database_driver}},'"${database_driver}"',g' -i ${envfile}

    sed 's,{{redis_host}},'"${redis_host}"',g' -i ${envfile}
}


initialize_hal() {
    echo "Initializing Hal container ..."

    PHP_VERSION=${PHP_VERSION:-7.1}
    PHP_MAX_CHILDREN=${PHP_MAX_CHILDREN:-5}

    sed 's,{{PHP_MAX_CHILDREN}},'"${PHP_MAX_CHILDREN}"',g' -i /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

    ./bin/cache-container
    ./bin/cache-doctrine-proxies
    ./bin/cache-routes
    ./bin/cache-templates
}

start_supervisor() {
    echo "Hal is starting! ..."
    supervisord -n -c /etc/supervisor/supervisord.conf
}

dddebug() {
    [ ! -d ./public ] && mkdir ./public
    echo "<?php \
    phpinfo();" > ./public/index.php
}

start() {
    /bin/wait_for_db.sh \
        --driver "${DB_DRIVER}" \
        --host "${DB_HOST}" \
        --port "${DB_PORT}" \
        --username "${DB_USERNAME}" \
        --password "${DB_PASSWORD}" \
        --database "${DB_DATABASE}" \
        --migration-table "phinx"

    migrate_db

    initialize_hal
    configure_hal
    # dddebug
    start_supervisor
}

stop() {
    echo "Stopping php-fpm..."
    supervisorctl stop php-fpm >/dev/null

    echo "Stopping nginx..."
    supervisorctl stop nginx >/dev/null

    echo "Stopping supervisord..."
    kill -15 $(cat /var/run/supervisord.pid)

    exit
}

ensure_not_empty "\$DB_DRIVER"      "${DB_DRIVER}"
ensure_not_empty "\$DB_HOST"        "${DB_HOST}"
ensure_not_empty "\$DB_PORT"        "${DB_PORT}"
ensure_not_empty "\$DB_USERNAME"    "${DB_USERNAME}"
ensure_not_empty "\$DB_PASSWORD"    "${DB_PASSWORD}"
ensure_not_empty "\$DB_DATABASE"    "${DB_DATABASE}"

ensure_is_installed "php"
ensure_is_installed "supervisord"

start

exit 0

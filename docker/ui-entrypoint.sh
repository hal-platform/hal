#!/bin/bash
set -eo pipefail

trap stop SIGINT SIGTERM

check_database_connection() {
    local readonly timeout="60"
    local readonly sleep_between_retry=2

    local readonly db_driver="$1"
    local readonly db_host="$2"
    local readonly db_port="$3"
    local readonly db_user="$4"
    local readonly db_pass="$5"
    local readonly db_database="$6"

    echo "Attempting to connect to database ..."

    case "${db_driver}" in
        mysql)
            prog="mysqladmin -h ${db_host} -u ${db_user} ${db_pass:+-p$db_pass} -P ${db_port} status"
            ;;
        pgsql)
            prog="/usr/bin/pg_isready"
            prog="${prog} -h ${db_host} -p ${db_port} -U ${db_user} -d ${db_database} -t 1"
            ;;
        sqlite)
            prog="touch /var/www/html/database/database.sqlite"
    esac

    SECONDS=0
    while [[ "$SECONDS" -lt "$timeout" ]]; do
        if [ ${prog} >/dev/null 2>&1 ]; then
            return
        else
            echo -n "."
            sleep "${sleep_between_retry}"
        fi
    done

    echo "Could not connect to database server! Aborting..."
    echo
}

check_database_configured() {
    local readonly migration_table=phinxlog
    local readonly driver="$1"
    local readonly db_host="$2"
    local readonly db_port="$3"
    local readonly db_user="$4"
    local readonly db_pass="$5"
    local readonly db_database="$6"

    if [ "${driver}" == "mysql" ] ; then

        if [[ "$(mysql -N -s -h "${db_host}" -u "${db_user}" "${db_pass:+-p$db_pass}" "${db_database}" -P "${db_port}" -e \
            "select count(*) from information_schema.tables where \
                table_schema='${db_database}' and table_name='${migration_table}';")" -eq 1 ]]; then
            echo "Table ${migration_table} exists! ..."
        else
            echo "Table ${migration_table} does not exist! ..."
            echo "Initializing Hal database ..."
            # create_db "${db_database}"
        fi

    elif [ "${driver}" == "pgsql" ] ; then

        export PGPASSWORD=${db_pass}
        if [[ "$(psql -h "${db_host}" -p "${db_port}" -U "${db_user}" -d "${db_database}" -c "SELECT to_regclass('${migration_table}');" | grep -c "${migration_table}")" -eq 1 ]]; then
            echo "Table ${migration_table} exists! ..."
        else
            echo "Table ${migration_table} does not exist! ..."
            echo "Initializing Hal database ..."
            # create_db "${db_database}"
        fi
    fi
}

migrate_db() {
    echo "Migrating Hal database ..."
    # ./bin/hal-phinx migrate
}

initialize_hal() {
    echo "Initializing Hal container ..."

    PHP_VERSION=${PHP_VERSION:-7.1}
    PHP_MAX_CHILDREN=${PHP_MAX_CHILDREN:-5}

    sed 's,{{PHP_MAX_CHILDREN}},'"${PHP_MAX_CHILDREN}"',g' -i /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
}

start_supervisor() {
    echo "Hal is starting! ..."
    supervisord -n -c /etc/supervisor/supervisord.conf
}

dddebug() {
    mkdir "/app/public"
    echo "<?php \
    phpinfo();" > /app/public/index.php
}

start() {
    initialize_hal
    #check_database_connection "${DB_DRIVER}" "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_PASSWORD}" "${DB_DATABASE}"
    #check_database_configured "${DB_DRIVER}" "${DB_HOST}" "${DB_PORT}" "${DB_USERNAME}" "${DB_PASSWORD}" "${DB_DATABASE}"
    migrate_db
    dddebug
    start_supervisor
}

stop() {
    echo ""
    /etc/init.d/gitlab stop

    echo "Stopping php-fpm..."
    supervisorctl stop php-fpm >/dev/null

    echo "Stopping nginx..."
    supervisorctl stop nginx >/dev/null

    echo "Stopping supervisord..."
    kill -15 $(cat /var/run/supervisord.pid)

    exit
}

start

exit 0

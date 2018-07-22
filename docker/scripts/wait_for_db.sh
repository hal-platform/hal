#!/bin/bash
set -eo pipefail

function log {
  local readonly msg="$@"
  >&2 echo -e "$msg"
}

function ensure_not_empty {
  local readonly arg_name="$1"
  local readonly arg_value="$2"

  if [[ -z "${arg_value}" ]]; then
    log "ERROR: The value for '${arg_name}' cannot be empty"
    exit 1
  fi
}

check_database_connection() {
    local readonly timeout="60"
    local readonly sleep_between_retry=2

    local readonly db_driver="$1"
    local readonly db_host="$2"
    local readonly db_port="$3"
    local readonly db_user="$4"
    local readonly db_pass="$5"
    local readonly db_database="$6"
    local exit_status

    echo "Attempting to connect to database: ${db_host} ..."

    case "${db_driver}" in
        mysql)
            prog="mysqladmin -h ${db_host} -u ${db_user} ${db_pass:+-p$db_pass} -P ${db_port} status"
            ;;
        pgsql)
            prog="/usr/bin/pg_isready"
            prog="${prog} -h ${db_host} -p ${db_port} -U ${db_user} -d ${db_database} -t 1"
            ;;
        sqlite)
            prog="touch ./database.sqlite"
    esac

    SECONDS=0
    while [[ "$SECONDS" -lt "$timeout" ]]; do
        set +e
        ${prog} >/dev/null 2>&1
        exit_status=$?
        set -e
        if [ ${exit_status} -eq 0 ]; then
            echo "Succesfully connected to database"
            return
        else
            echo -n "."
            sleep "${sleep_between_retry}"
        fi
    done

    echo
    echo "Could not connect to database server! Aborting..."
    exit 1
}

check_database_configured() {
    local readonly db_driver="$1"
    local readonly db_host="$2"
    local readonly db_port="$3"
    local readonly db_user="$4"
    local readonly db_pass="$5"
    local readonly db_database="$6"
    local readonly migration_table="$7"

    if [ "${db_driver}" == "mysql" ] ; then

        if [[ "$(mysql -N -s -h "${db_host}" -u "${db_user}" "${db_pass:+-p$db_pass}" "${db_database}" -P "${db_port}" -e \
            "select count(*) from information_schema.tables where \
                table_schema='${db_database}' and table_name='${migration_table}';")" -eq 1 ]]; then
            echo "Table ${migration_table} exists! ..."
        else
            echo "Table ${migration_table} does not exist! ..."
            echo "Initializing Hal database ..."
        fi

    elif [ "${db_driver}" == "pgsql" ] ; then

        export PGPASSWORD=${db_pass}
        if [[ "$(psql -h "${db_host}" -p "${db_port}" -U "${db_user}" -d "${db_database}" -c "SELECT to_regclass('${migration_table}');" | grep -c "${migration_table}")" -eq 1 ]]; then
            echo "Table ${migration_table} exists! ..."
        else
            echo "Table ${migration_table} does not exist! ..."
            echo "Initializing Hal database ..."
        fi
    fi
}

configure_db_migrater() {
    local readonly db_driver="$1"
    local readonly db_host="$2"
    local readonly db_port="$3"
    local readonly db_user="$4"
    local readonly db_pass="$5"
    local readonly db_database="$6"
    local readonly migration_table="$7"

    echo "Configuring phinx ..."

    get_phinx_config \
        "${migration_table}" > ./phinx.yml

    if [ "${db_driver}" == "pgsql" ] ; then
        get_postgres_config \
            "$db_host" \
            "$db_port" \
            "$db_database" \
            "$db_user" \
            "$db_pass" >> ./phinx.yml

    elif [ "${db_driver}" == "mysql" ] ; then
        get_mysql_config \
            "$db_host" \
            "$db_port" \
            "$db_database" \
            "$db_user" \
            "$db_pass" >> ./phinx.yml


    elif [ "${db_driver}" == "sqlite" ] ; then
        get_sqlite_config \
            './database.sqlite' >> ./phinx.yml
    fi
}

get_phinx_config() {
    echo "
paths:
    migrations: '%%PHINX_CONFIG_DIR%%/migrations'

migration_base_class: 'Hal\Core\Database\PhinxMigration'

environments:
    default_migration_table: '${1}'
    default_database: 'connection'

    connection:"
}

get_postgres_config() {
    echo "
        adapter: 'pgsql'
        host: '${1}'
        port: ${2}
        name: '${3}'
        user: '${4}'
        pass: '${5}'
        charset: 'utf8'
"
}

get_mysql_config() {
    echo "
        adapter: 'mysql'
        host: '${1}'
        port: ${2}
        name: '${3}'
        user: '${4}'
        pass: '${5}'
        charset: 'utf8'
"
}
get_sqlite_config() {
    echo "
        adapter: 'sqlite'
        name: '${1}
"
}

function run_script {
    local driver
    local host
    local port
    local username
    local password
    local database
    local migration_table

    while [[ $# > 0 ]]; do
        local key="$1"

        case "$key" in
            --driver)
                driver="$2" ; shift
                ;;
            --host)
                host="$2" ; shift
                ;;
            --port)
                port="$2" ; shift
                ;;
            --username)
                username="$2" ; shift
                ;;
            --password)
                password="$2" ; shift
                ;;
            --database)
                database="$2" ; shift
                ;;
            --migration-table)
                migration_table="$2" ; shift
                ;;
            *)
                echo "ERROR: Unrecognized argument: $key"
                exit 1
                ;;
        esac

        shift
    done

    ensure_not_empty "--driver" "$driver"
    ensure_not_empty "--host" "$host"
    ensure_not_empty "--port" "$port"
    ensure_not_empty "--username" "$username"
    ensure_not_empty "--password" "$password"
    ensure_not_empty "--database" "$database"
    ensure_not_empty "--migration-table" "$migration_table"

    check_database_connection "$driver" "$host" "$port" "$username" "$password" "$database"
    check_database_configured "$driver" "$host" "$port" "$username" "$password" "$database" "phinx"
    configure_db_migrater     "$driver" "$host" "$port" "$username" "$password" "$database" "phinx"
}

run_script $@

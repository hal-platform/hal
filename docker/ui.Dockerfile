# STAGE 1
###############################################################################

FROM node:10.7.0-stretch as frontend_build

ARG hal_version=master
ARG archive_url

ENV hal_version ${hal_version:-master}
ENV archive_url ${archive_url:-https://api.github.com/repos/hal-platform/hal/tarball/${hal_version}}

WORKDIR /app

RUN curl -sSLo code.tgz \
    ${archive_url} && \
    tar -xzf code.tgz --strip-components=1 && \
    rm -r code.tgz

RUN yarn install \
    --production \
    --no-progress \
    && \
        yarn run deploy \
    && \
        rm -rf ./node_modules

# STAGE 2
###############################################################################

FROM halplatform/php:frontend as backend_build

# Install optional dependencies
RUN apt-get update && \
    apt-get install -y \
        git \
    && rm -rf "/var/lib/apt/lists/*"

WORKDIR /app

COPY --from=frontend_build /app .

RUN composer install \
    --no-dev --optimize-autoloader

# Force install of phinx
RUN ./vendor/bin/hal-phinx

# STAGE 3
###############################################################################

FROM nginx:1.15.1

EXPOSE 8000
CMD ["/sbin/entrypoint.sh"]

ENV PHP_VERSION      7.1
ENV DEBIAN_FRONTEND  noninteractive

# Install system and php dependencies
RUN apt-get update && \
    apt-get install -y \
        bzip2 \
        curl \
        supervisor \
        apt-transport-https \
        ca-certificates \
    && \
    curl -sS \
        -o /etc/apt/trusted.gpg.d/php.gpg \
        https://packages.sury.org/php/apt.gpg \
    && \
    echo "deb https://packages.sury.org/php/ stretch main" | tee \
        /etc/apt/sources.list.d/php.list \
    && \
    apt-get update && \
    mkdir -p /usr/share/man/man1 && \
    mkdir -p /usr/share/man/man7 && \
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-ldap \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-phpdbg \
        php${PHP_VERSION}-sodium \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-xml \
        php${PHP_VERSION}-zip \
    && rm -rf "/var/lib/apt/lists/*"

# Install optional dependencies
RUN apt-get update && \
    apt-get install -y \
        sqlite \
        postgresql-client \
        mysql-client \
    && rm -rf "/var/lib/apt/lists/*"

RUN ln -sf /dev/stdout /var/log/php${PHP_VERSION}-fpm.log

RUN touch /var/run/nginx.pid && \
    chown -R www-data:root /var/run/nginx.pid \
    && \
    touch /var/run/fpm.pid && \
    chown -R www-data:root /var/run/fpm.pid

WORKDIR /app

COPY --from=backend_build /bin/composer /bin/composer

COPY --chown=www-data:root \
    --from=backend_build /app .

RUN mkdir -p /usr/share/nginx/cache && \
    mkdir -p /var/cache/nginx && \
    mkdir -p /var/lib/nginx && \
    chown www-data:root \
        /app && \
    chown -R www-data:root \
        /usr/share/nginx/cache \
        /var/cache/nginx \
        /var/lib/nginx/ \
        /etc/php/${PHP_VERSION}/fpm/pool.d

USER www-data

COPY scripts/wait_for_db.sh /bin/wait_for_db.sh
COPY scripts/entrypoint.sh  /sbin/entrypoint.sh

COPY conf/fpm.conf          /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf

COPY conf/nginx.conf        /etc/nginx/nginx.conf
COPY conf/nginx.site.conf   /etc/nginx/conf.d/default.conf

COPY conf/supervisord.conf  /etc/supervisor/supervisord.conf
COPY conf/.env.docker       /.env.default

USER root

RUN chmod -R g+rw \
    /usr/share/nginx/cache \
    /var/cache/nginx \
    /var/lib/nginx

USER www-data

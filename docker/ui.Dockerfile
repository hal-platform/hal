FROM nginx:1.15.1

EXPOSE 8000
CMD ["/sbin/entrypoint.sh"]

ENV PHP_VERSION      7.1
ENV COMPOSER_VERSION 1.6.3
ENV DEBIAN_FRONTEND  noninteractive

# Install system and php dependencies
RUN apt-get update && \
    apt-get install -y \
        bzip2 \
        curl \
        git \
        sqlite \
        supervisor \
        \
        apt-transport-https \
        ca-certificates \
        lsb-release \
    && \
    curl -sS \
        -o /etc/apt/trusted.gpg.d/php.gpg \
        https://packages.sury.org/php/apt.gpg \
    && \
    echo "deb https://packages.sury.org/php/ $(lsb_release -sc) main" | tee \
        /etc/apt/sources.list.d/php.list \
    && \
    apt-get update && \
    apt-get install -y \
        php${PHP_VERSION} \
        php${PHP_VERSION}-curl \
        php${PHP_VERSION}-fpm \
        php${PHP_VERSION}-mbstring \
        php${PHP_VERSION}-mysql \
        php${PHP_VERSION}-opcache \
        php${PHP_VERSION}-pgsql \
        php${PHP_VERSION}-phpdbg \
        php${PHP_VERSION}-sodium \
        php${PHP_VERSION}-sqlite3 \
        php${PHP_VERSION}-xml \
        && rm -rf "/var/lib/apt/lists/*"

# Install composer
RUN curl -sSo /tmp/composer-setup.php \
        https://getcomposer.org/installer \
        && \
    curl -sSo /tmp/composer-setup.sig \
        https://composer.github.io/installer.sig \
        && \
    php -r "if (hash('SHA384', file_get_contents('/tmp/composer-setup.php')) !== trim(file_get_contents('/tmp/composer-setup.sig'))) { unlink('/tmp/composer-setup.php'); echo 'Invalid installer' . PHP_EOL; exit(1); }" \
        && \
    php /tmp/composer-setup.php \
        --version=${COMPOSER_VERSION} \
        --filename=composer \
        --install-dir=bin \
        && \
    rm -f "/tmp/composer-setup.*"

RUN ln -sf /dev/stdout /var/log/php${PHP_VERSION}-fpm.log

RUN touch /var/run/nginx.pid && \
    chown -R www-data:root /var/run/nginx.pid \
    && \
    touch /var/run/fpm.pid && \
    chown -R www-data:root /var/run/fpm.pid

RUN mkdir -p /app && \
    mkdir -p /usr/share/nginx/cache && \
    mkdir -p /var/cache/nginx && \
    mkdir -p /var/lib/nginx && \
    chown -R www-data:root \
        /app \
        /usr/share/nginx/cache \
        /var/cache/nginx \
        /var/lib/nginx/ \
        /etc/php/${PHP_VERSION}/fpm/pool.d

WORKDIR /app
USER www-data

COPY conf/fpm.conf          /etc/php/${PHP_VERSION}/fpm/pool.d/www.conf
COPY ui-entrypoint.sh       /sbin/entrypoint.sh

COPY conf/nginx.conf        /etc/nginx/nginx.conf
COPY conf/nginx.site.conf   /etc/nginx/conf.d/default.conf

COPY conf/supervisord.conf  /etc/supervisor/supervisord.conf

USER root

RUN chmod -R g+rw \
    /usr/share/nginx/cache \
    /var/cache/nginx \
    /var/lib/nginx

USER www-data

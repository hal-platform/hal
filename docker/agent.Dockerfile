# STAGE 1
###############################################################################

FROM halplatform/php:frontend as agent_build

ARG hal_version=master
ARG archive_url

ENV DEBIAN_FRONTEND  noninteractive

ENV hal_version ${hal_version:-master}
ENV archive_url ${archive_url:-https://api.github.com/repos/hal-platform/hal-agent/tarball/${hal_version}}

WORKDIR /app

RUN curl -sSLo code.tgz \
    ${archive_url} && \
    tar -xzf code.tgz --strip-components=1 && \
    rm -r code.tgz

# Install optional dependencies
RUN apt-get update && \
    apt-get install -y \
        git \
    && rm -rf "/var/lib/apt/lists/*"

RUN composer install \
    --no-dev --optimize-autoloader

RUN ./bin/bundle-phar

# COPY conf/.env.docker /.env.default

# STAGE 2
###############################################################################

FROM halplatform/php:frontend as job_runner

EXPOSE 4646
CMD ["/usr/bin/nomad", "agent", "-config=/etc/nomad"]

RUN adduser -S -s /bin/bash -u 1001 -G root hal

WORKDIR /app

COPY --chown=hal:root \
    --from=agent_build /app/hal.phar .

ENV NOMAD_VERSION 0.8.4
RUN \
    curl -Lo "nomad.zip" "https://releases.hashicorp.com/nomad/${NOMAD_VERSION}/nomad_${NOMAD_VERSION}_linux_amd64.zip" \
    \
    && unzip "nomad.zip" \
    && rm "nomad.zip" \
    \
    && chmod +x "nomad" \
    && mv "nomad" "/usr/bin/nomad"

RUN mkdir -p /var/lib/nomad && \
    mkdir -p /etc/nomad && \
    chown hal:root \
        /app \
        /var/lib/nomad \
        /etc/nomad

USER hal

COPY conf/nomad.hcl         /etc/nomad/nomad.hcl

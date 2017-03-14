FROM ruby:2.3-alpine

ENV SLATE_VERSION 1.5

WORKDIR /app

RUN apk --no-cache add --virtual .build-deps \
    build-base \
    curl \
    ruby-dev \
    libxml2-dev \
    libxslt-dev \
    linux-headers \
    libffi-dev \
    zlib-dev \
    nodejs

RUN mkdir -p /opt/src && \
    cd /opt/src && \
    curl -LO "https://github.com/lord/slate/archive/v$SLATE_VERSION.tar.gz" && \
    tar -xzf "v$SLATE_VERSION.tar.gz" && \
    cp -R slate-$SLATE_VERSION/* /app/ && \
    rm -rf /opt/src

RUN bundle config build.nokogiri --use-system-libraries && \
    bundle install

VOLUME ["/app/source"]
VOLUME ["/app/build"]

ENTRYPOINT ["bundle", "exec", "middleman"]
CMD ["build", "--clean"]

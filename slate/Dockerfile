# To build:
# docker build -t slate slate

# To run:
# docker run -d -p 4567:4567 --name slate -v $(pwd)/slate:/app/source slate --force

FROM debian:jessie

RUN apt-get update
RUN apt-get install -yq ruby ruby-dev build-essential git
RUN gem install --no-ri --no-rdoc bundler

ENV SLATE_VERSION v1.2.1
WORKDIR /app

RUN git clone https://github.com/tripit/slate.git . \
    && git checkout tags/$SLATE_VERSION -b $SLATE_VERSION \
    && bundle install

EXPOSE 4567

ENTRYPOINT ["bundle", "exec", "middleman", "server"]

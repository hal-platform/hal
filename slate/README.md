# API Documentation

API docs are generated with slate. Slate is a template for creating docs using the middleman static site generator. These
technologies are built in ruby.

# Serving docs in development

While writing docs in a development environment, use the docker image to start up a middleman server, that automatically
rebuilds the docs. This container will serve the web site at `http://localhost:4567`.

Use the included script `bin/slate` to manage the container. It will run the container, building it if necessary, or
restart it if already running.

# Compiling docs for deployment

While the halslate container is running:

1. `docker exec halslate rake build`

   > Generate docs within the container

2. `docker cp halslate:/app/build public/docs/api`

   > Copy docs to `public/docs/api`

3. Commit the changes

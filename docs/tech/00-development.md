



TODO check if .env.dist can be used as is on dev env



# Development environment

You will need a running Docker environment to install this app locally.
Set it up by following the guides available [docker](https://docs.docker.com/install/) and [docker compose](https://docs.docker.com/compose/install/).

## Set up

In root and symfony directories, type `make help` to get available commands.

* Build and run the application with `make run`. On first execution, this will build all Docker images needed to compose the network.

> Once all containers are up, you will be connected to the application container and have access to a shell inside the Symfony installation.

* Inside the application container, install the project dependencies with `make install` and reset the database by running `make reset`.

> The application is now running and can be accessed at [http://localhost:81]().

*  Connecting to the local MySQL server

> The local port of the container running the MySQL server is bound to the host port number **3307**.
Connect to 127.0.0.1:3307 in order to access the MySQL server.

## Accessing the application container

If you detach from the application container, run `docker-compose exec php bash` to reconnect and have access to a shell.

From here, you can run the following commands to fully set-up the environment:

```
composer install
yarn install
yarn encore dev
```

Go to http://127.0.0.1:81 to access the project.

## Rebuild Docker images

Docker images can be rebuilt with the `make build` command.

## Shutting down all containers

Docker containers can be stopped with the `make stop` command.

[Go back](../../README.md)

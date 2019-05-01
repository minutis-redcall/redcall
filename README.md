# The Red Call


This project concerns volunteers that do social activities (roams, emergency night shelters...) and those who do first aid. As volunteers can be located anywhere in Paris (or elsewhere), tools are required to synchronize people when criticial situations occur.

RedCall.io is a messaging tool for head of emergencies. They use it to ask for volunteer's availability, gather volunteer's answers, and fill up ambulances or assistance units with the required people and skills.

This project was initially developed by the BlaBlaCar team as part of a Coding Night event, check out more details on [that medium blog post](https://medium.com/blablacar-tech/extending-our-principles-outside-blablacar-the-redalert-project-cf50110f0848).

## Installation procedure (development)

You will need a running Docker environment to install this app locally.
Set it up by following the guides available [here]([https://docs.docker.com/get-started/]).

* Build and run the application with `make run`. On first execution, this will build all Docker images needed to compose the network.

> Once all containers are up, you will be connected to the application container and have access to a shell inside the Symfony installation.

* Inside the application container, install the project dependencies with `make install` and reset the database by running `make reset`.

> The application is now running and can be accessed at [http://localhost:81]().

### Connecting to the MySQL server

The local port of the container running the MySQL server is bound to the host port number **3307**.
Connect to 127.0.0.1:3307 in order to access the MySQL server.

### Accessing the application container

If you detach from the application container, run `docker-compose exec php bash` to reconnect and have access to a shell.

From here, you can run the following commands to fully set-up the environment:

```
composer install
yarn install
yarn encore dev
```

### Rebuild Docker images

Docker images can be rebuilt with the `make build` command.

### Shutting down all containers

Docker containers can be stopped with the `make stop` command.


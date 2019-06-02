# The Red Call

This Red Cross project concerns volunteers that do social activities (roams, emergency night shelters...) and those who do first aid. As volunteers can be located anywhere in Paris (or elsewhere), tools are required to synchronize people as fast as possible when criticial situations occur.

RedCall.io is a messaging tool for head of emergencies. They use it to ask for volunteer's availability, gather volunteer's answers, and fill up ambulances or assistance units with the required people and skills.

This project was initially developed by the BlaBlaCar team as part of a Coding Night event, check out more details on [that medium blog post](https://medium.com/blablacar-tech/extending-our-principles-outside-blablacar-the-redalert-project-cf50110f0848).

## Installation

This project runs in PHP >=7.1 with the Symfony4 framework.
It requires a MySQL or MariaDB database and Yarn for assets management.

### Development

This project runs on Docker for development purposes.
Many tools have been developed to disable or mock external providers,
so you can get ready to work in a few seconds.

Find set up instructions [here](docs/tech/00-development.md).

### Staging and production

This project is built to be deployed in production on Google App Engine.

- [Setting up a new Google Cloud Platform project](docs/tech/01-google-cloud-platform.md)
- [Setting up a Google App Engine instance](docs/tech/02-google-app-engine.md)
- [Setting up a custom domain name to your instance](docs/tech/03-custom-domain-name.md)

Configuration is located in [deploy/prod](deploy/prod) and [deploy/preprod](deploy/preprod) directories,
for each environment you need, you should rename `dotenv.dist` into `dotenv` and fill up every variable
by following the next sections.

- [Setting up a Google Service Account](docs/tech/04-google-service-account.md)
- [Setting up Symfony](docs/tech/05-configure-symfony.md)
- [Setting up Google reCaptcha](docs/tech/06-google-recaptcha.md)
- [Setting up Google Cloud SQL](docs/tech/07-google-cloud-sql.md)
- [Setting up Sentry](docs/tech/08-configure-sentry.md)
- [Setting up Mapbox](docs/tech/09-configure-mapbox.md)
- [Setting up Nexmo](docs/tech/10-configure-nexmo.md)
- [Setting up Pegass](docs/tech/11-configure-pegass.md)

To deploy, run the following commands:

```
cd deploy
sh ./deploy.sh <env>
```

To set-up your first account, [check that doc](docs/tech/12-enable-first-admin.md):

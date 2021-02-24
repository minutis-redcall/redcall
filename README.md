# Minutis RedCall

This Red Cross project concerns volunteers that do social activities (roams, emergency night shelters...) and those who
do first aid. As volunteers can be located anywhere in Paris (or elsewhere), tools are required to synchronize people as
fast as possible when criticial situations occur.

RedCall.io is a messaging tool for head of emergencies. They use it to ask for volunteer's availability, gather
volunteer's answers, and fill up ambulances or assistance units with the required people and skills.

[![Présentation de Redcall](https://img.youtube.com/vi/0g8YDprUqg8/0.jpg)](https://www.youtube.com/watch?v=0g8YDprUqg8)

This project was initially developed by the BlaBlaCar team as part of a Coding Night event, check out more details
on [that medium blog post](https://medium.com/blablacar-tech/extending-our-principles-outside-blablacar-the-redalert-project-cf50110f0848)
. It is now maintained and supported by the Minutis team by French Red Cross volunteers.

## Development

This project runs in PHP 7 with the Symfony 5 framework. It requires a MySQL or MariaDB database and Yarn for assets
management. Frontend is built with jQuery 3 and Bootstrap 3. 

This project runs on Docker for development purposes. Many tools have been developed to disable or mock external
providers, so you can get ready to work in a few seconds.

Set up instructions [here](docs/tech/00-development.md), and read the [CONTRIBUTING](CONTRIBUTING.md).

### Staging and production

In production, this project runs on Cloud App Engine with a MariaDB instance on Cloud SQL. 

- [Setting up GCP](docs/tech/01-gcp.md)

Configuration is located in [deploy/prod](deploy/prod) and [deploy/preprod](deploy/preprod) directories, for each
environment you need, you should rename `dotenv.dist` into `dotenv` and fill up every variable by following the next
sections.

- [Setting up Symfony](docs/tech/05-configure-symfony.md)
- [Setting up Google reCaptcha](docs/tech/06-google-recaptcha.md)
- [Setting up Mapbox](docs/tech/09-configure-mapbox.md)
- [Setting up Twilio](docs/tech/10-configure-twilio.md)
- [Setting up Sendgrid](docs/tech/10-configure-sendgrid.md)
- [Setting up Slack](docs/tech/13-configure-slack.md)
- [Setting up MJML](docs/tech/14-configure-mjml.md)
- [Setting up Phrase](docs/tech/15-phraseapp.md)

RedCross only:

- [Setting up Pegass](docs/tech/11-configure-pegass.md)

Once everything is configured, go to the following page:

- [First deployment](docs/tech/12-first-deployment.md)

To deploy, run the following commands:

```
cd deploy
sh ./deploy.sh <env>
```

To sync the database, use the migration script:

```
cd deploy
sh ./migrate.sh <env>
```


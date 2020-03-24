# RedCall

Welcome.

Before starting, you can have a look to the [docs](docs/user/fr/README.md) in order to fully understand
the purpose and the scope of the project.

## Communication

In order to stay tuned, ask for help or any question, join the team on slack:

http://redcall.slack.com

## Technical installation

### Development

The project runs with PHP >=7.1, Symfony 4, MariaDB and Yarn for assets.

You can set up the project [by using docker](docs/tech/00-development.md) 
or using the embedded server. 

I am personally doing the 2nd option, so there may be surprises while using Docker as 
I did not run it since several months. Installation is quite standard:

```
composer install
yarn install
php bin/console server:start
```

When configuring environment variables, you will need some api keys or credentials. 

Those ones won't have difficulties for you, that's a symfony classic:

```
APP_ENV=dev
APP_SECRET=some secret
WEBSITE_URL=http://127.0.0.1:8000
DATABASE_HOST=127.0.0.1
DATABASE_URL=mysql://root:password@$DATABASE_HOST/redcall?unix_socket=/path/to/socket
```

The following ones are only used by deployment scripts, so you can ignore them:

```
GCP_PROJECT_NAME=redcall
GCP_BASTION_INSTANCE=bastion
```

The following ones can be ignored because they are only used by the 
[Minutis authentication](https://github.com/redcall-io/app/blob/master/symfony/src/Security/Authenticator/MinutisAuthenticator.php),
except MINUTIS_SUPPORT which is also rendered in some pages.

```
MINUTIS_JWT_PUBLIC_KEY_URL=xxx
MINUTIS_SUPPORT=support.minutis@croix-rouge.fr
MINUTIS_URL=xxx
```

The next ones are only used in production, and in the legacy authentication system. They will soon disappear.

```
GOOGLE_RECAPTCHA_SITE_KEY=key
GOOGLE_RECAPTCHA_SECRET=secret
```

In production, as we run in GCP, we need a Google service account in order to write logs on stackdriver.
No need to fill those variables on dev env.

```
GOOGLE_API_KEY=
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
```

The next one can be useful in development environment if you want to work on the geo location part.

You can create an API key for free on Mapbox, follow [this document](https://github.com/redcall-io/app/blob/master/docs/tech/09-configure-mapbox.md).

```
MAPBOX_API_KEY=some key
```

Pegass is a Red Cross application that allow scheduling activities or search for volunteers. We use
its API to scrap and regularly update RedCall volunteers list.

The Pegass credentials should be your Pegass credentials at the Red Cross. They are only required if
you wish to work on this part of the app, though.

```
PEGASS_LOGIN=yourlogin
PEGASS_PASSWORD=yourpassword
```

Finally, we may need to configure mailer and sms providers. But their clients are mocked in the Sandbox,
so no real SMS or email is sent (you can "read" or "answer" them in a dedicated interface located
at the bottom of the dev environment).

```
MAILER_FROM=contact@redcall.io
MAILER_URL='smtp://auto@redcall.io:password@mail.provider.com:465?encryption=ssl'
TWILIO_ACCOUNT_SID=xxx
TWILIO_AUTH_TOKEN=xxx
TWILIO_NUMBER=xxx
```

To create your first user, register it and use the commands (user:verify, user:trust, user:admin) in
order to enable it.

To create skills, structures and volunteers, connect to the platform and click on the "Sandbox" button,
you will find there some fixtures generator.

Once you've some volunteers and structures, go into "Administration", "Manage users" and bind your
user to a "nivol" and some structures.

Now you can play!

### Preproduction & Production

Most of the time, I will be here to push your changes in preproduction or in production. Just ask!

Otherwise, the project is built to run on Google App Engine. Once you'll get signed (either a professional contract
or as a red cross volunteer), you will get the write accesses to this repository, to the prod / preprod projects in GCP,
and to the right dotenvs.

Then, you will be able to use scripts in [deploy](deploy) to perform operations (can be changed for
a more professional tool, all ideas welcome).

## Code

### Internal bundles

As you can see [here](https://github.com/redcall-io), there are several bundles that were created for the project. 

During our participation to the FIC 2020 (where hunters were invited to pentest the platform), I needed a great velocity to handle the flow and moved those bundles [here](https://github.com/redcall-io/app/tree/master/symfony/bundles) inside the main repository.

We will sooner or later move them back in their own repository, so it's good to remind the good practice: all bundles should be usable in any other project than redcall.

Two exceptions though:

- password-login-bundle is legacy, only used in dev, and will soon be removed (in favour of an authentication through Minutis which is already operating)

- sandbox-bundle is a development bundle used to mock SMS and email providers (and soon authentication), application entities, managers, repos etc can be used there without issues

### Permissions

- ROLE_USER for connected users (but not necessarly having access to his features)

- ROLE_TRUSTED for users allowed to use the platform

- CAMPAIGN to check that a user can access a given campaign

- STRUCTURE to check that a user is allowed to trigger a structure

- VOLUNTEER to check that a user is allowed to trigger a volunteer

You can check the voters for more details.

### Code guidelines

Some simple rules to speak the same language.

This project is built on top of Symfony 4 (so far), it's a classic MVC:

- views should be mobile friendly

- views are translated in french and english, even though medical english is probably wrong. Let's
continue this practice in order to scale up someday without hassle.

- controller gather required vars through managers and renders a view: 
https://github.com/redcall-io/app/blob/master/symfony/src/Controller/Admin/PegassController.php#L69

- a manager can call its own repository or other managers/services: 
https://github.com/redcall-io/app/blob/master/symfony/src/Manager/CampaignManager.php#L18

- a repository cannot be called by anything else than its own manager, even if there are no business logic
https://github.com/redcall-io/app/blob/master/symfony/src/Manager/CampaignManager.php#L97

- a repository only perform sql queries, and it does it through doctrine ORM (no native queries yet, we may move to another platform than cloud sql)
https://github.com/redcall-io/app/blob/master/symfony/src/Repository/CampaignRepository.php#L17

- only repositories perform queries, if you find some legacy code still performing queries, you're welcome to fix it

- the Doctrine migration system is used in order to build the database schema, so once you've added/updated an entity, run:

```
php bin/console make:migration
php bin/console doctrine:migrations:migrate
```

### Documentation

If you add a new feature or change the behavior of an existing one, please edit the documentation as well.
 

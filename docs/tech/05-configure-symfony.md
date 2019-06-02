## Configure Symfony

Directories [prod](../../deploy/prod) and [preprod](../../deploy/preprod) 
contain a `dotenv` file that you need to fill up with the right configuration.

**Objective**:
This project is developed using the Symfony framework, and Symfony requires
some basic configuration. Most importantly, emails should be set with care
as redcall.io is firstly a communication tool.

### Project configuration

- `APP_ENV` should be filled up with the symfony environment: `dev` 
for debugging, `test` for CI, and `prod` for production and preproduction 
environments.

- `APP_SECRET` is a random string that will be used as an initialization 
vector for many symfony features, such as session storage, random-kind 
functions initialization etc; the goal being to serve many symfony apps on
the same server without resource sharing, concurrence etc.

- `WEBSITE_URL` is the hardcoded root URL of your application, it will be used
when generating links that will be used in communications (emails and SMSes).
Hardcoding the URL instead of relying on headers is the best way to protect
your app against host injections and redirect attacks.

- `MAILER_FROM` and `MAILER_URL` describe the address from which will be sent 
emails, and its configuration. If the example given in your `dotenv` is not
working and/or for more inforamtions about the URL format,
check out the [symfony documentation](https://symfony.com/doc/current/reference/configuration/swiftmailer.html).

[Go back](../../README.md)

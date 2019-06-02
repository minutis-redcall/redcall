
## Creating Sentry key

Directories [prod](../../deploy/prod) and [preprod](../../deploy/preprod) 
contain a `dotenv` file that you need to fill up with the right configuration.

Sentry is an online error management system that provides nice dashboards,
debugging tools and slack alerting tools. It's free for 1 GitHub user, so
make sure to create a GitHub account with a shared email.

**Objective**:
We currently bound Sentry to a Slack tech channel in order to be quickly
aware of critical issues on the platform. Even though having a Slack
running is not mandatory for the project, having Sentry is integrated
and thus is, so far, mandatory.

1. Go to GitHub

2. Create or sign-in with your shared GitHub account

3. Go to https://sentry.io/

4. Sign-in with your shared GitHub account

5. Create a new organization (ex: redcall)

6. Create a new project (ex: redcall-prod)

### Project configuration

Once done, you will get the DSN you should put in `SENTRY_DSN` variable.

[Go back](../../README.md)

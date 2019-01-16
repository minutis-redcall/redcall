# _The Red Alert_, alerting system for the _Red Cross_

## Local installation

* Copy the content of the `.env.dist` file to a new `.env` file in the project root directory.
* Set up the environment variable specific to your configuration.
* Run `make install` to install all the project dependencies.
* Run `make reset` to create/reset the database and load fixtures.
 
## Run the application on a local environment

Run the command `make run` to start a local PHP server and go to [http://127.0.0.1:8000](http://127.0.0.1:8000).

## Emails

For development, you can set your Gmail account in order to receive emails.

```
MAILER_URL='gmail://<your gmail username>:<your app password>@localhost'
```

If you are using Google 2FA, you'll need to create an app password:
https://myaccount.google.com/apppasswords and use the google-generated password instead of yours.

## Access management

Once you registered your first user, enable it and make it admin using the console:

```
# Validate your email just like if you clicked on the link
./bin/console user:verify <your email>

# Allow your account to use the services
./bin/console user:trust <your email>

# Allow your account to allow other accounts to use the services
./bin/console user:admin <your email>
```

## Crons

In order to clear up useless data in your database, you can add the following commands in your crontab, once a day:

```
0 0 * * * <path/to/the/project>/symfony/bin/console user:cron --env=prod
```

Automatic import of the volunteers:

```
0 1 * * * <path/to/the/project>/symfony/bin/console volunteer:import --env=prod
```

## Wehbooks in development environment

You can simulate Nexmo payloads by using the following requests:

Delivery Receipt:

> http://127.0.0.1:8000/webhooks/delivery-receipt?err-code=0&key=hY8WUKvTcDO7&message-timestamp=2018-10-21%2009%3A03%3A18&messageId=%MESSAGEID%&msisdn=%VOLUNTEERNO%&network-code=20801&price=0.03000000&scts=1810211103&status=delivered&to=33757915111

Inbound SMS:

> http://127.0.0.1:8000/webhooks/inbound-sms?key=hY8WUKvTcDO7&msisdn=%VOLUNTEERNO%&to=33757915111&messageId=%MESSAGEID%&text=%TEXT%&type=text&keyword=2&message-timestamp=2018-10-21+09%3A16%3A35

- Replace `%VOLUNTEERNO%` by the volunteer phone number in Nexmo format (33600000000)
- Replace `%MESSAGEID%` by the message id (you have it on the database, `select message_id from Message`)
- Replace `%TEXT%` by the message body.

You can also use the built-in SMS sandbox.

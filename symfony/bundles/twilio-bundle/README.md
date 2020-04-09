# Twilio Bundle

Manage incoming and outgoing SMS and voice calls in your Symfony application.

## What is it?

Purpose of this bundle is to manage sending and receiving SMS and voice calls in a way independent from your application logic.

In order to stay KISS, this bundle does not handle cost optimizations (transliteration, character set filtering etc). You should implement them by yourself.

## Installation

Set the following environment variables:

```
TWILIO_ACCOUNT_SID=your account sid
TWILIO_AUTH_TOKEN=your auth token
TWILIO_NUMBER=the phone number to send and receive messages
```

Add the bundle in the project:

```php
// config/bundles.php

return [
    // ...
    Bundles\TwilioBundle\TwilioBundle::class => ['all' => true],
];
```

Make sure Twilio webhooks are not behind your security firewall. 

```yaml
  # security.yaml

  access_control:
    # ...
    - { path: '^/twilio', role: 'IS_AUTHENTICATED_ANONYMOUSLY' }
```

Add the webhooks routing:

```yaml
# annotations.yaml

twilio:
  resource: '@TwilioBundle/Controller/'
  type: annotation
```

Make sure to add the `twilio:price` command in your cron jobs (executed once every hour) in order to store prices associated with your usage.

On Twilio console:

- to receive messages, add `<your website>/twilio/incoming-message` in your "Phone Number"
configuration page (example: `https://d7185d61.ngrok.io/twilio/incoming-message`).

- to receive voice calls, add `<your website>/twilio/incoming-call` in your "Phone Number"
configuration page (example: `https://d7185d61.ngrok.io/twilio/incoming-call`).

## Usage




## Helpers

- Provides a command `twilio:sms` to send an sms to any phone number

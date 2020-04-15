# Twilio Bundle

Manage incoming and outgoing SMS and voice calls in your Symfony application.

## What is it?

Purpose of this bundle is to manage sending and receiving SMS and voice calls in a way independent from your application logic.

In order to stay KISS, this bundle does not handle cost optimizations (transliteration, character set filtering etc). You should implement them by yourself.

## Installation

Set the following environment variables:

```
WEBSITE_URL=your website base url, used to generate absolute urls without possible host injections
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

### Send a text message

Sending SMS is as simple as injecting the service and calling it:

```php
<?php

namespace App\Controller;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Manager\TwilioMessageManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="demo", name="demo_")
 */
class DemoController
{
    /** @var TwilioMessageManager */
    private $messageManager;

    /**
     * @param TwilioMessageManager $messageManager
     */
    public function __construct(TwilioMessageManager $messageManager)
    {
        $this->messageManager = $messageManager;
    }

    /**
     * @Route("/send-sms", name="sms")
     */
    public function sendSms()
    {
        /** @var TwilioMessage $twilioMessage */
        $twilioMessage = $this->messageManager->sendMessage(
            // Message recipient
            '+33600000000',

            // Message to send (cost optimizations warning, take care to
            // use transliteration to replace/remove special chars)
            'This is a demo of SMS',

            // Your application context (to bind this message to your app logic,
            // will be sent back in events)
            ['custom_data' => 42, 'time' => new \DateTime()]
        );

        return new Response('Sms sent!');
    }
}
```

### Receive a text message

In order to receive SMS, you need to subscribe to the `onMessageReceived` event.

```php
<?php

namespace App\EventSubscriber;

use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioMessageEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twilio\TwiML\MessagingResponse;

class DemoSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param LoggerInterface|null $logger
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::MESSAGE_RECEIVED => 'onMessageReceived',
        ];
    }

    public function onMessageReceived(TwilioMessageEvent $event)
    {
        /** @var TwilioMessage $twilioMessage */
        $twilioMessage = $event->getMessage();

        // Do something with the message
        $this->logger->info('Received a new SMS', [
            'from' => $twilioMessage->getFromNumber(),
            'body' => $twilioMessage->getMessage(),
        ]);

        // Optionally, reply to the message
        $response = new MessagingResponse();
        $message = $response->message('');
        $message->body('Thank you for your answer!');
        $event->setResponse($response);
    }
}
```

### Send a voice call

### Receive a voice call

### Track status & prices


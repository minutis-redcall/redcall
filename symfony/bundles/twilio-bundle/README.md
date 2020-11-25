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
TWILIO_CALL=the phone number to send and receive phone calls
TWILIO_SMS=the phone number to send and receive text messages
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
            ['custom_data' => 42]
        );

        return new Response('Sms sent!');
    }
}
```

### Receive a text message

In order to receive SMS, you need to subscribe to the `TwilioEvents::MESSAGE_RECEIVED` event.

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

### Send and receive voice calls

To create an outgoing voice call, you should initialize it:

```php
<?php

namespace App\Controller;

use Bundles\TwilioBundle\Manager\TwilioCallManager;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * @Route(path="demo", name="demo_")
 */
class DemoController
{
    /** @var TwilioCallManager */
    private $callManager;

    /**
     * @param TwilioCallManager    $callManager
     */
    public function __construct(TwilioCallManager $callManager)
    {
        $this->callManager = $callManager;
    }

    /**
     * @Route("/initialize-call", name="call")
     */
    public function initializeCall()
    {
        $this->callManager->sendCall(
            // Call recipient
            '33600000000',
            // Your application context (to bind this call to your app logic,
            // will be sent back in events)
            [
                'workflow' => 'package_ready', 
                'customer_name' => 'John Doe',
            ]
        );

        return new Response('Call initialized!');
    }
}
```

You can then manage all other events in a subscriber:

- `TwilioEvents::CALL_RECEIVED` for incoming voice calls
- `TwilioEvents::CALL_ESTABLISHED` for outgoing voice calls
- `TwilioEvents::CALL_KEY_PRESSED` for any interaction needed

```php
<?php

namespace App\EventSubscriber;

use Bundles\TwilioBundle\Entity\TwilioCall;
use Bundles\TwilioBundle\Event\TwilioCallEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Twilio\TwiML\VoiceResponse;

class DemoSubscriber implements EventSubscriberInterface
{
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger ?: new NullLogger();
    }

    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::CALL_ESTABLISHED => 'onCallEstablished',
            TwilioEvents::CALL_RECEIVED => 'onCallReceived',
            TwilioEvents::CALL_KEY_PRESSED => 'onCallEstablished',
        ];
    }

    public function onCallEstablished(TwilioCallEvent $event)
    {
        /** @var TwilioCall $twilioCall */
        $twilioCall = $event->getCall();

        $this->logger->info('Established a new ongoing call', [
            'from' => $twilioCall->getFromNumber(),
            'context' => $twilioCall->getContext(),
        ]);

        $customerName = $twilioCall->getContext()['customer_name'];

        // A response is needed otherwise Twilio will hang up
        $response = new VoiceResponse();
        $response->say(sprintf('Good day %s!', $customerName));
        $response->pause(['length' => 1]);
        $response->say('Your package is now ready to be delivered.');

        $gather = $response->gather(['numDigits' => 1]);
        $gather->say('Let us know if you are home now by pressing 1, otherwise, press 2.');

        $event->setResponse($response);
    }

    public function onCallReceived(TwilioCallEvent $event)
    {
        /** @var TwilioCall $twilioCall */
        $twilioCall = $event->getCall();

        $this->logger->info('Received an incoming call', [
            'from' => $twilioCall->getFromNumber(),
            'context' => $twilioCall->getContext(),
            'digit' => $event->getKeyPressed(),
        ]);

        $twilioCall->setContext([
            'some_custom_data' => 42,
            'workflow' => 'ask_delivery_status',
        ]);

        $response = new VoiceResponse();
        $response->say('Welcome to our delivery service.');
        $response->pause(['length' => 1]);
        $gather = $response->gather(['numDigits' => 12]);
        $gather->say('Please press the 12 digits of your tracking number.');

        $event->setResponse($response);
    }

    public function onKeyPressed(TwilioCallEvent $event)
    {
        /** @var TwilioCall $twilioCall */
        $twilioCall = $event->getCall();

        // Do something with the call
        $this->logger->info('Received a new key press', [
            'from' => $twilioCall->getFromNumber(),
            'context' => $twilioCall->getContext(),
            'digit' => $event->getKeyPressed(),
        ]);

        $response = new VoiceResponse();

        // Answer differently according to what you've put in the context
        if ('ask_delivery_status' === $twilioCall->getContext()['workflow']) {

            // ...

        }

        if ('package_ready' === $twilioCall->getContext()['workflow']) {
            switch ($event->getKeyPressed()) {
                case 1:
                    $response->say('Okay! We are advising our driver to deliver your package now.');
                    break;
                case 2:
                    $response->say('Okay! We will call you back tomorrow, thank you.');
                    break;
                default:
                    $response->say('Sorry, we did not understand this answer.');
                    break;
            }
        }

        $event->setResponse($response);
    }
}
```

### Price tracking

With Twilio, all costs are asynchronous, you can't know how much costed a call or
a message when you trigger it. Thus, there is a command that try to fetch prices
for every message or call SIDs known by the application.

In order to handle prices on your application, you can add the `twilio:price`
command in your cron jobs, ran every hour. Prices will be fetched 48 times for
each SIDs for which prices are unknown.

You can then subscribe to the following events if you want to bind those costs
to your application logic (billing etc):

- `TwilioEvents::MESSAGE_PRICE_UPDATED` to get the TwilioMessage for which price is available
- `TwilioEvents::CALL_PRICE_UPDATED` to get the TwilioCall for which the price is available

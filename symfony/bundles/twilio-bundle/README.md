# Twilio Bundle

Purpose of this bundle is to manage sending and receiving SMS in a way independent from your application logic. 

As SMS are expensive, we track everything by storing outbounds, inbounds, costs and delivery statuses. Your app is then free to use those information for business-related uses.

```
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
| id | uuid                                 | direction | message     | from_number | to_number   | sid                                | status    | price    | unit | context |
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
|  3 | 5e54a6a2-27db-448a-be4e-0b20199dc84d | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SMzjQJKmGYdz0cAf2rgymILNTv7PmhAPz7 | delivered | -0.07600 | USD  | []      |
|  4 | 7303e136-e832-481b-a38b-f1603db9ba4a | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM4FFJqdViROSy2hI33cUJeOcx1qkbn9lV | queued    | -0.07600 | USD  | []      |
|  5 | e37abd25-b611-4bdb-99b1-85641892ff70 | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SMk9Sdf5JDMBp5GskUGns82y1Iw4o0hQQC | delivered | -0.07600 | USD  | []      |  6 | 6013617f-874f-4f44-9690-ec581d2f98fd | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM7Ze1IZCMJkhi3pxV52UAhJy6385739m2 | delivered | -0.07600 | USD  | []      |
|  7 | 8c821e08-4a75-46a6-9459-e8fe10503b3f | outbound  | hello world | 33xxxxxxxxx | 33yyyyyyyyy | SM5ZFPrZYC9YA4COVmrmrWDtfQPX0XMtme | delivered | -0.07600 | USD  | []      |
|  8 | 0531a363-5dfa-4764-9365-6d638993ac02 | inbound   | Test        | 33yyyyyyyyy | 33xxxxxxxxx | SM5nAFUMkOFub9YuovkEbX0H5jBrJMETIw | receiving | -0.00750 | USD  | NULL    |
+----+--------------------------------------+-----------+-------------+-------------+-------------+------------------------------------+-----------+----------+------+---------+
```

In order to stay KISS, this bundle does not handle cost optimizations (transliteration, character set filtering etc). You should implement them by yourself.

## Installation

Set the following environment variables:

```
TWILIO_ACCOUNT_SID=your account sid
TWILIO_AUTH_TOKEN=your auth token
TWILIO_NUMBER=the phone number to send and receive messages
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

On Twilio website, to receive messages, add `<your website>/twilio/reply` in your "Phone Number"
configuration page (example: `https://d7185d61.ngrok.io/twilio/reply`).

## Send an SMS and keep a log of it

Send sms through Twilio API. Once sent, an SMS log is stored along with some custom context for further uses. The `TwilioEvents::MESSAGE_SENT` event is also dispatched once message is sent to Twilio. 

```php
<?php

namespace App\Controller;

use App\Entity\Communication;use Bundles\TwilioBundle\SMS\Twilio;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ReplyController extends BaseController
{
    /**
     * @var Twilio
     */
    private $twilio;

    /**
     * @param Twilio $twilio
     */
    public function __construct(Twilio $twilio)
    {
        $this->twilio = $twilio;
    }

    /**
     * @Route(name="test", path="/test/{id}")
     * @Template()
     */
    public function test(Communication $communication)
    {
        $user = $this->getUser();
        
        // Send a simple message
        $this->twilio->sendMessage('336123456789', 'Hello, world!');

        // Attach a context for later use (billing, etc)
        $this->twilio->sendMessage($user->getPhoneNumber(), 'Hello, world!', [
            'communication_id' => $communication->getId(),
        ]);

        return new Response();
    }
}
```

## Track every SMS delivery status

This bundle expose a secured delivery status webhook automatically, and that webook fires a `TwilioEvents::STATUS_UPDATED` event when that status is updated.

```php
<?php

namespace App\EventSubscriber;

use App\Manager\CommunicationManager;
use Bundles\TwilioBundle\Entity\TwilioMessage;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwilioSubscriber implements EventSubscriberInterface
{
    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @param CommunicationManager $communicationManager
     */
    public function __construct(CommunicationManager $communicationManager)
    {
        $this->communicationManager = $communicationManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::STATUS_UPDATED => 'onStatusUpdated',
        ];
    }

    public function onStatusUpdated(TwilioEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $communicationId = $twilioMessage->getContext()['communication_id'] ?? null;
        if (!$communicationId) {
            return;
        }

        $communication = $this->communicationManager->find($communicationId);
        if (!$communication) {
            return;
        }

        if (TwilioMessage::STATUS_DELIVERED === $twilioMessage->getStatus()) {
            // In order to add green on some progress bar?
            $communication->incrementSuccessDelivery();
        }
    }
}
```

## Get and store cost of all SMS sent

Provides a command `twilio:price` that you need to cron in order to fetch and store sms prices. Works for sms sent, but also sms received. A `TwilioEvents::PRICE_UPDATED` event is then fired in order for your code to apply its own business on it. 

```php
<?php

namespace App\EventSubscriber;

use App\Manager\CommunicationManager;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwilioSubscriber implements EventSubscriberInterface
{
    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @param CommunicationManager $communicationManager
     */
    public function __construct(CommunicationManager $communicationManager)
    {
        $this->communicationManager = $communicationManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::PRICE_UPDATED => 'onPriceUpdated',
        ];
    }

    /**
     * @param TwilioEvent $event
     */
    public function onPriceUpdated(TwilioEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $communicationId = $twilioMessage->getContext()['communication_id'] ?? null;
        if (!$communicationId) {
            return;
        }

        $communication = $this->communicationManager->find($communicationId);
        if (!$communication) {
            return;
        }

        $communication->increaseCost(-1 * (float)$twilioMessage->getPrice());
        $communication->setCurrency($twilioMessage->getUnit());

        $this->communicationManager->save($communication);
    }
}
```

## Receive SMS replies

Store the inbound message for further operations. Dispatch a symfony Event to notify of the new sms inbound.

```php
<?php

namespace App\EventSubscriber;

use App\Manager\CommunicationManager;
use Bundles\TwilioBundle\Event\TwilioEvent;
use Bundles\TwilioBundle\TwilioEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class TwilioSubscriber implements EventSubscriberInterface
{
    /**
     * @var CommunicationManager
     */
    private $communicationManager;

    /**
     * @param CommunicationManager $communicationManager
     */
    public function __construct(CommunicationManager $communicationManager)
    {
        $this->communicationManager = $communicationManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            TwilioEvents::MESSAGE_RECEIVED => 'onMessageReceived',
        ];
    }

    /**
     * @param TwilioEvent $event
     *
     * @throws \Doctrine\ORM\NonUniqueResultException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    public function onMessageReceived(TwilioEvent $event)
    {
        $twilioMessage = $event->getMessage();

        $communicationId = $this->communicationManager->handleAnswer($twilioMessage->getFromNumber(), $twilioMessage->getMessage());
        if ($communicationId) {
            $twilioMessage->setContext(['communication_id' => $communicationId]);
        }
    }
}
```

## Helpers

- Provides a command `twilio:sms` to send an sms to any phone number

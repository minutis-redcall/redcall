# Google Cloud Tasks for Symfony

Aim to simplify asynchronous tasks in Symfony for App Engine through Google Cloud Tasks.
- simple interface to implement
- request signature integrated

## Usage

A task is a service that aim to execute a piece of code asynchronously.

- Synchornously, you will create and send the task to Google

```php
$this->taskSender->fire(SendSmsTask::class, [
    'phone' => 1234, 
    'message' => 'Hello, world!'
]);
```

- Asynchronously, the `execute()` method inside your task implementation will be called.

```php
class SendSmsTask implements TaskInterface
{
    private $smsSender;

    public function __construct(SmsSender $smsSender)
    {
        $this->smsSender = $smsSender;
    }

    public function execute(array $context)
    {
        $this->smsSender->send($context['phone'], $context['message']);
    }

    public function getQueueName() : string
    {
        return getenv('GCP_QUEUE_GENERIC');
    }
}
```

## Configuration

Create a queue on Google Cloud Task

```
gcloud tasks queues create generic
gcloud tasks queues update generic \
    --max-dispatches-per-second=100 \
    --max-concurrent-dispatches=500 \
    --max-attempts=100 \
    --min-backoff=1s \
    --max-backoff=10s
```

Add the bundle in the project:

```php
// config/bundles.php
return [
    // ...
    Bundles\GoogleTaskBundle\GoogleTaskBundle::class => ['all' => true],
];
```

Make sure Twilio webhooks are not behind your security firewall. 

```yaml
  # security.yaml
  access_control:
    # ...
    # Google Cloud Tasks
    - { path: '^/cloud-task$', role: 'IS_AUTHENTICATED_ANONYMOUSLY' }
```

Add the webhooks routing:

```yaml
# annotations.yaml
google_task:
  resource: '@GoogleTaskBundle/Controller/'
  type: annotation
```

Automatically handle your task implementations

```yaml
# config/services.yaml
services:
    _instanceof:
        Bundles\GoogleTaskBundle\Api\TaskInterface:
            tags: ['google_task']
```

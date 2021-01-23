# api-bundle

Provides useful tools:

- authentication using existing symfony users
- api keys management
- webhooks configuration management

Request & response model objects framework:

- no transformations thanks to the request param converter, response subscriber and exception handler
- payloads auto-validated
- self documented through native & symfony docblocks & annotations

...

## Requirements

You should make sure users that need to access API management pages have a ROLE_DEVELOPER role. The implementation of
the admin side that will let you set users as developers are at your discretion.

Sample:

```
    public function getRoles() : array
    {
        return [
            'ROLE_USER',
            'ROLE_DEVELOPER',
        ];
    }
```

## Installation

In `config/bundles.php`:

```php
return [
    // ...
    Bundles\ApiBundle\ApiBundle::class => ['all' => true],
];
```

In `config/packages/security.yaml`:

```yaml
security:

  firewalls:
    # ...
    api:
      pattern: '^/api'
      provider: user_provider
      stateless: true
      anonymous: false
      logout: ~
      guard:
        authenticators:
          - Bundles\ApiBundle\Security\Authenticator\ApiAuthenticator

  access_control:
    # ...
    - { path: '^/developer', roles: [ 'ROLE_DEVELOPER' ] }
```

In `config/routes/annotations.yaml`:

```yaml
api:
  resource: '@ApiBundle/Controller/'
    type: annotation
```

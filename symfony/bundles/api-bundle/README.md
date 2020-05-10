# api-bundle

Provides tools:
- ux to manage api keys tied to users
- ux to manage webhooks configuration
- authentication

...

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
      # todo

  access_control:
    # ...
    - { path: '^/developer', roles: ['ROLE_DEVELOPER'] }
```

In `config/routes/annotations.yaml`:

```yaml
api:
 resource: '@ApiBundle/Controller/'
   type: annotation
```


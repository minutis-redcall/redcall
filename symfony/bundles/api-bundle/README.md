# api-bundle

Provides tools:
- ux to manage api keys tied to users
- ux to manage webhooks configuration
- authentication

...

## Requirements

You should make sure users that need to access API management
pages have a ROLE_DEVELOPER role. The implementation of the admin
side that will let you set users as developers are at your discretion.

Sample:

```
    public function getRoles() : array
    {
        $roles = ['ROLE_USER'];

        if ($this->isDeveloper) {
            $roles[] = 'ROLE_DEVELOPER';
        }

        return $roles;
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

## Usage


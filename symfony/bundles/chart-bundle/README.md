# chart-bundle

Provide tools to create charts based on SQL queries.

## Requirements

2 roles are used in this bundle:

- `ROLE_DEVELOPER` is needed to create charts
- `ROLE_ADMIN` is required to access charts

## Installation

In `config/bundles.php`:

```php
return [
    // ...
    Bundles\ChartBundle\ChartBundle::class => ['all' => true],
];
```

In `config/routes/annotations.yaml`:

```yaml
chart:
  resource: '@ChartBundle/Controller/'
    type: annotation
```


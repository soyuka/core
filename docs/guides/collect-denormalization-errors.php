## Collecting Denormalization Errors

When submitting data you can collect denormalization errors using the [COLLECT_DENORMALIZATION_ERRORS option](https://symfony.com/doc/current/components/serializer.html#collecting-type-errors-while-denormalizing).

It can be done directly in the `#[ApiResource]` attribute (or in the operations):

```php
<?php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;

#[ApiResource(
    collectDenormalizationErrors: true
)]
class Book
{
    public ?bool $boolean;
    public ?string $property1;
}
```

If the submitted data has denormalization errors, the HTTP status code will be set to `422 Unprocessable Content` and the response body will contain the list of errors:

```json
{
    "@context": "/api/contexts/ConstraintViolationList",
    "@type": "ConstraintViolationList",
    "hydra:title": "An error occurred",
    "hydra:description": "boolean: This value should be of type bool.\nproperty1: This value should be of type string.",
    "violations": [
        {
            "propertyPath": "boolean",
            "message": "This value should be of type bool.",
            "code": "0"
        },
        {
            "propertyPath": "property1",
            "message": "This value should be of type string.",
            "code": "0"
        }
    ]
}
```

You can also enable collecting of denormalization errors globally in the [Global Resources Defaults](https://api-platform.com/docs/core/configuration/#global-resources-defaults).

# Validation

API Platform takes care of validating the data sent to the API by the client (usually user data entered through forms).
By default, the framework relies on [the powerful Symfony Validator Component](http://symfony.com/doc/current/validation.html)
for this task, but you can replace it with your preferred validation library such as [the PHP filter extension](https://www.php.net/manual/en/intro.filter.php) if you want to.

<p align="center" class="symfonycasts"><a href="https://symfonycasts.com/screencast/api-platform/validation?cid=apip"><img src="images/symfonycasts-player.png" alt="Validation screencast"><br>Watch the Validation screencast</a></p>

## Symfony

In relation to Symfony, API Platform hooks a [validation listener](./symfony-event-listeners) executed during the `kernel.view` event. It checks if the validation is disabled on your Operation via [Operation::validate](/reference/Metadata/Operation#validate), then it calls the [Symfony validator](https://symfony.com/doc/current/components/validator.html) with the [Operation::validationContext](/reference/Metadata/Operation#validationContext). 

If one wants to [use validation on a Delete operation](/guide/validate-data-on-a-delete-operation) you'd need to implement this yourself. 

## Error Levels 

If you need to customize error levels we recommend to follow the [Symfony documentation](https://symfony.com/doc/current/validation/severity.html) and add payload fields.
You can then retrieve the payload field by setting the `serialize_payload_fields` to an empty `array` in the API Platform configuration:

```yaml
# api/config/packages/api_platform.yaml

api_platform:
    validator:
        serialize_payload_fields: ~
```

Then, the serializer will return all payload values in the error response.

If you want to serialize only some payload fields, define them in the config like this:

```yaml
# api/config/packages/api_platform.yaml

api_platform:
    validator:
        serialize_payload_fields: [ severity, anotherPayloadField ]
```

In this example, only `severity` and `anotherPayloadField` will be serialized.

<!-- TODO: move this to hypermedia explanation -->
## JSON Schema and OpenAPI integration

### Specification property restrictions

API Platform generates specification property restrictions based on Symfony’s built-in validator.

For example, from [`Regex`](https://symfony.com/doc/4.4/reference/constraints/Regex.html) constraint API
 Platform builds the JSON Schema [`pattern`](https://github.com/OAI/OpenAPI-Specification/blob/main/versions/3.0.3.md#schema-object) restriction.

For building custom property schema based on custom validation constraints you can create a custom class
for generating property scheme restriction.

To create property schema, you have to implement the [`PropertySchemaRestrictionMetadataInterface`](https://github.com/api-platform/core/blob/main/src/Symfony/Validator/Metadata/Property/Restriction/PropertySchemaRestrictionMetadataInterface.php).
This interface defines only 2 methods:

* `create`: to create property schema
* `supports`: to check whether the property and constraint is supported

Here is an implementation example:

```php
namespace App\PropertySchemaRestriction;

use ApiPlatform\Metadata\ApiProperty;
use Symfony\Component\Validator\Constraint;
use App\Validator\CustomConstraint;

final class CustomPropertySchemaRestriction implements PropertySchemaRestrictionMetadataInterface
{
    public function supports(Constraint $constraint, ApiProperty $propertyMetadata): bool
    {
        return $constraint instanceof CustomConstraint;
    }

    public function create(Constraint $constraint, ApiProperty $propertyMetadata): array 
    {
      // your logic to create property schema restriction based on constraint
      return $restriction;
    }
}
```

### Open Vocabulary Generated from Validation Metadata

API Platform automatically detects Symfony's built-in validators and generates schema.org IRI metadata accordingly. This allows for rich clients such as the Admin component to infer the field types for most basic use cases.

The following validation constraints are covered:

Constraints                                                                           | Vocabulary                        |
--------------------------------------------------------------------------------------|-----------------------------------|
[`Url`](https://symfony.com/doc/current/reference/constraints/Url.html)               | `https://schema.org/url`           |
[`Email`](https://symfony.com/doc/current/reference/constraints/Email.html)           | `https://schema.org/email`         |
[`Uuid`](https://symfony.com/doc/current/reference/constraints/Uuid.html)             | `https://schema.org/identifier`    |
[`CardScheme`](https://symfony.com/doc/current/reference/constraints/CardScheme.html) | `https://schema.org/identifier`    |
[`Bic`](https://symfony.com/doc/current/reference/constraints/Bic.html)               | `https://schema.org/identifier`    |
[`Iban`](https://symfony.com/doc/current/reference/constraints/Iban.html)             | `https://schema.org/identifier`    |
[`Date`](https://symfony.com/doc/current/reference/constraints/Date.html)             | `https://schema.org/Date`          |
[`DateTime`](https://symfony.com/doc/current/reference/constraints/DateTime.html)     | `https://schema.org/DateTime`      |
[`Time`](https://symfony.com/doc/current/reference/constraints/Time.html)             | `https://schema.org/Time`          |
[`Image`](https://symfony.com/doc/current/reference/constraints/Image.html)           | `https://schema.org/image`         |
[`File`](https://symfony.com/doc/current/reference/constraints/File.html)             | `https://schema.org/MediaObject`   |
[`Currency`](https://symfony.com/doc/current/reference/constraints/Currency.html)     | `https://schema.org/priceCurrency` |
[`Isbn`](https://symfony.com/doc/current/reference/constraints/Isbn.html)             | `https://schema.org/isbn`          |
[`Issn`](https://symfony.com/doc/current/reference/constraints/Issn.html)             | `https://schema.org/issn`          |

## Query parameter validation

<!-- TODO: missing documentation -->
By default query parameter validation is on, you can remove it using:

```yaml
# api/config/packages/api_platform.yaml

api_platform:
    validator:
        query_parameter_validation: false

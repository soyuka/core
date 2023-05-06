<?php
// ---
// slug: collecting-denormalization-errors
// name: Collecting Denormalization Errors
// executable: true
// tags: serializer
// ---

// # Collecting Denormalization Errors
namespace App\ApiResource {

    use ApiPlatform\Metadata\ApiResource;

// When submitting data you can collect denormalization errors using the [COLLECT_DENORMALIZATION_ERRORS option](https://symfony.com/doc/current/components/serializer.html#collecting-type-errors-while-denormalizing).
// To do so enable the [`collectDenormalizationErrors`](/docs/Metadata/AttributeResource#collectDenormalizationErrors) option.
    #[ApiResource(
        collectDenormalizationErrors: true
    )]
    class Book
    {
        public ?bool $boolean;
        public ?string $property1;
    }

// If the submitted data has denormalization errors, the HTTP status code will be set to `422 Unprocessable Content` and the response body will contain the list of errors:
// ```json
// {
//   "@context": "\contexts\ConstraintViolationList",
//   "@type": "ConstraintViolationList",
//   "hydra:title": "An error occurred",
//   "hydra:description": "boolean: This value should be of type bool.\nproperty1: This value should be of type string.",
//   "violations": [
//     {
//       "propertyPath": "boolean",
//       "message": "This value should be of type bool.",
//       "code": "0"
//     },
//     {
//       "propertyPath": "property1",
//       "message": "This value should be of type string.",
//       "code": "0"
//     }
//   ]
// }
// ```
// You can also enable collecting of denormalization errors globally in the [Global Resources Defaults](https://api-platform.com/docs/core/configuration/#global-resources-defaults).
}

namespace App\Playground {
    use Symfony\Component\HttpFoundation\Request;

    function request(): Request
    {
        return Request::create('/books', 'POST', server: [
            'HTTP_ACCEPT' => 'application/ld+json',
            'CONTENT_TYPE' => 'application/json'
        ], content: '{
  "boolean": -1,
  "property1": 2
}'
        );
    }
}

<?php
// ---
// slug: disabling-the-pagination
// name: Disabling the pagination
// position: 10
// executable: true
// ---

// Paginating collections is generally accepted as a good practice. It allows browsing large collections without too
// much overhead as well as preventing [DOS attacks](https://en.wikipedia.org/wiki/Denial-of-service_attack).
// However, for small collections, it can be convenient to fully disable the pagination.
//
// ## Disabling the Pagination Globally
//
// The pagination can be disabled for all resources using this configuration:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_enabled: false
// ```
//
// ## Disabling the Pagination For a Specific Resource
//
// It can also be disabled for a specific resource:
//
// ```yaml
// # api/config/api_platform/resources.yaml
// App\Entity\Book:
//     paginationEnabled: false
// ```
// ```xml
// <!-- api/config/api_platform/resources.xml -->
// <resources xmlns="https://api-platform.com/schema/metadata/resources-3.0"
//            xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
//            xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
//            https://api-platform.com/schema/metadata/resources-3.0.xsd">
//     <resource class="App\Entity\Book" paginationEnabled=false>
//         <!-- ... -->
//     </resource>
// </resources>
// ```

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationEnabled: false)]
    class Book
    {
        // ...
    }
}

// ## Disabling the Pagination Client-side
//
// You can configure API Platform to let the client enable or disable the pagination. To activate this feature globally,
// use the following configuration:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_client_enabled: true
//     collection:
//         pagination:
//             enabled_parameter_name: pagination # optional
// ```
//
// The pagination can now be enabled or disabled by adding a query parameter named `pagination`:
//
// * `GET /books?pagination=false`: disabled
// * `GET /books?pagination=true`: enabled
//
// Any value accepted by the [`FILTER_VALIDATE_BOOLEAN`](https://www.php.net/manual/en/filter.filters.validate.php)
// filter can be used as the value.
//
// ## Disabling the Pagination Client-side For a Specific Resource
//
// The client ability to disable the pagination can also be set in the resource configuration:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationClientEnabled: true)]
    class Book
    {
        // ...
    }
}

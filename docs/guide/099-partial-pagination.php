<?php
// ---
// slug: partial-pagination
// name: Partial Pagination
// position: 10
// executable: true
// ---

// When using the default pagination, a `COUNT` query will be issued against the current requested collection. This may
// have a performance impact on big collections. The downside is that the information about the last page is lost
// (e.g.: `hydra:last`).
//
// ## Partial Pagination Globally
//
// The partial pagination retrieval can be configured for all resources:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_partial: true # Disabled by default
// ```
//
// ## Partial Pagination For a Specific Resource

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationPartial: true)]
    class Book
    {
        // ...
    }
}

// ## Partial Pagination Client-side Globally
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_client_partial: true # Disabled by default
//     collection:
//         pagination:
//             partial_parameter_name: 'partial' # Default value
// ```
//
// The partial pagination retrieval can now be changed by toggling a query parameter named `partial`: `GET /books?partial=true`.
//
// ## Partial Pagination Client-side For a Specific Resource

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationClientPartial: true)]
    class Book
    {
        // ...
    }
}

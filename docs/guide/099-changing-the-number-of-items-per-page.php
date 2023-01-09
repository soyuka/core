<?php
// ---
// slug: changing-the-number-of-items-per-page
// name: Changing the Number of Items per Page
// position: 10
// executable: true
// ---

// In the same manner, the number of items per page is configurable and can be set client-side.
//
// ## Changing the Number of Items per Page Globally
//
// The number of items per page can be configured for all resources:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_items_per_page: 30 # Default value
// ```
//
// ## Changing the Number of Items per Page For a Specific Resource
//
// It can also be configured for a specific resource:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationItemsPerPage: 30)]
    class Book
    {
        // ...
    }
}

// ## Changing the Number of Items per Page Client-side Globally
//
// You can configure API Platform to let the client configure the number of items per page. To activate this feature
// globally, use the following configuration:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_client_items_per_page: true
//     collection:
//         pagination:
//             items_per_page_parameter_name: itemsPerPage # Default value
// ```
//
// The number of items per page can now be changed adding a query parameter named `itemsPerPage`: `GET /books?itemsPerPage=20`.
//
// ## Changing the Number of Items per Page Client-side For a Specific Resource
//
// Changing the number of items per page can be enabled (or disabled) for a specific resource:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationClientItemsPerPage: true)]
    class Book
    {
        // ...
    }
}

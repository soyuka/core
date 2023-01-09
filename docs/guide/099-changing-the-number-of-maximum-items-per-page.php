<?php
// ---
// slug: changing-the-number-of-maximum-items-per-page
// name: Changing the Number of Maximum Items per Page
// position: 10
// executable: true
// ---

// ## Changing Maximum Items Per Page Globally
//
// The number of maximum items per page can be configured for all resources:
//
// ```yaml
// # api/config/packages/api_platform.yaml
// api_platform:
//     defaults:
//         pagination_maximum_items_per_page: 50
// ```
//
// ## Changing Maximum Items Per Page For a Specific Resource

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(paginationMaximumItemsPerPage: 50)]
    class Book
    {
        // ...
    }
}

// ## Changing Maximum Items Per Page For a Specific Resource Collection Operation

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\GetCollection;

    #[ApiResource]
    #[GetCollection(paginationMaximumItemsPerPage: 50)]
    class Book
    {
        // ...
    }
}

<?php
// ---
// slug: cursor-based-pagination
// name: Cursor-Based Pagination
// position: 10
// executable: true
// ---

// To configure your resource to use the cursor-based pagination, select your unique sorted field as well as the direction you’ll like the pagination to go via filters and enable the `paginationViaCursor` option.
// Note that for now you have to declare a `RangeFilter` and an `OrderFilter` on the property used for the cursor-based pagination.
//
// The following configuration also works on a specific operation:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiFilter;
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
    use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;

    #[ApiResource(
        paginationViaCursor: [
            ['field' => 'id', 'direction' => 'DESC']
        ],
        paginationPartial: true
    )]
    #[ApiFilter(RangeFilter::class, properties: ["id"])]
    #[ApiFilter(OrderFilter::class, properties: ["id" => "DESC"])]
    class Book
    {
        // ...
    }
}

// To know more about cursor-based pagination take a look at [this blog post on medium (draft)](https://medium.com/@sroze/74fd1d324723).

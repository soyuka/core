<?php
// ---
// slug: controlling-the-behavior-of-the-doctrine-orm-paginator
// name: Controlling The Behavior of The Doctrine ORM Paginator
// position: 10
// executable: true
// ---

// The [PaginationExtension](https://github.com/api-platform/core/blob/main/src/Doctrine/Orm/Extension/PaginationExtension.php) of API Platform performs some checks on the `QueryBuilder` to guess, in most common cases, the correct values to use when configuring the Doctrine ORM Paginator:
//
// * `$fetchJoinCollection` argument: Whether there is a join to a collection-valued association. When set to `true`, the Doctrine ORM Paginator will perform an additional query, in order to get the correct number of results.
//
//     You can configure this using the `paginationFetchJoinCollection` attribute on a resource or on a per-operation basis:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\GetCollection;

    #[ApiResource(paginationFetchJoinCollection: false)]
    #[GetCollection]
    #[GetCollection(name: 'get_custom', paginationFetchJoinCollection: true)]
    class Book
    {
        // ...
    }
}

// * `setUseOutputWalkers` setter: Whether to use output walkers. When set to `true`, the Doctrine ORM Paginator will use output walkers, which are compulsory for some types of queries.
//
//     You can configure this using the `paginationUseOutputWalkers` attribute on a resource or on a per-operation basis:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\GetCollection;

    #[ApiResource(paginationUseOutputWalkers: false)]
    #[GetCollection]
    #[GetCollection(name: 'get_custom', paginationUseOutputWalkers: true)]
    class Book
    {
        // ...
    }
}

// For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.

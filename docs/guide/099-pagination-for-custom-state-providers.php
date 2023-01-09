<?php
// ---
// slug: pagination-for-custom-state-providers
// name: Pagination for Custom State Providers
// position: 10
// executable: true
// ---

// If you are using custom state providers (not the provided Doctrine ORM, ODM or ElasticSearch ones)
// and if you want your results to be paginated, you will need to return an instance of a
// `ApiPlatform\State\Pagination\PartialPaginatorInterface` or
// `ApiPlatform\State\Pagination\PaginatorInterface`.
//
// A few existing classes are provided to make it easier to paginate the results:
//
// * `ApiPlatform\State\Pagination\ArrayPaginator`
// * `ApiPlatform\State\Pagination\TraversablePaginator`

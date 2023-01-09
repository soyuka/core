# Pagination

<p align="center" class="symfonycasts"><a href="https://symfonycasts.com/screencast/api-platform/pagination?cid=apip"><img src="../distribution/images/symfonycasts-player.png" alt="Pagination screencast"><br>Watch the Pagination screencast</a></p>

API Platform has native support for paged collections. Pagination is enabled by default for all collections. Each collection
contains 30 items per page.
The activation of the pagination and the number of elements per page can be configured from:

* the server-side (globally or per resource)
* the client-side, via a custom GET parameter (disabled by default)

When issuing a `GET` request on a collection containing more than 1 page (here `/books`), a [Hydra collection](http://www.hydra-cg.com/spec/latest/core/#collections)
is returned. It's a valid JSON(-LD) document containing items of the requested page and metadata.

```json
{
  "@context": "/contexts/Book",
  "@id": "/books",
  "@type": "hydra:Collection",
  "hydra:member": [
    {
      "@id": "/books/1",
      "@type": "https://schema.org/Book",
      "name": "My awesome book"
    },
    {
        "_": "Other items in the collection..."
    },
  ],
  "hydra:totalItems": 50,
  "hydra:view": {
    "@id": "/books?page=1",
    "@type": "hydra:PartialCollectionView",
    "hydra:first": "/books?page=1",
    "hydra:last": "/books?page=2",
    "hydra:next": "/books?page=2"
  }
}
```

Hypermedia links to the first, the last, previous and the next page in the collection are displayed as well as the number
of total items in the collection.

* [Changing the Pagination Parameter Name](/docs/guide/changing-the-pagination-parameter-name)
* [Disabling the Pagination](/docs/guide/disabling-the-pagination)
* [Changing the Number of Items per Page](/docs/guide/changing-the-number-of-items-per-page)
* [Changing the Number of Maximum Items per Page](/docs/guide/changing-the-number-of-maximum-items-per-page)
* [Partial Pagination](/docs/guide/partial-pagination)
* [Cursor-Based Pagination](/docs/guide/cursor-based-pagination)
* [Controlling The Behavior of The Doctrine ORM Paginator](/docs/guide/controlling-the-behavior-of-the-doctrine-orm-paginator)
* [Custom Controller Action](/docs/guide/custom-controller-action)
* [Pagination for Custom State Providers](/docs/guide/pagination-for-custom-state-providers)

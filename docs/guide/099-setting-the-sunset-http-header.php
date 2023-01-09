<?php
// ---
// slug: setting-the-sunset-http-header
// name: Setting the `Sunset` HTTP Header to Indicate When a Resource or an Operation Will Be Removed
// position: 10
// executable: true
// ---

// [The `Sunset` HTTP response header](https://tools.ietf.org/html/draft-wilde-sunset-header) indicates that a URI is likely to become unresponsive at a specified point in the future.
// It is especially useful to indicate when a deprecated URL will not be available anymore.
//
// Thanks to the `sunset` attribute, API Platform makes it easy to set this header for all URLs related to a resource class:

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;

    #[ApiResource(
        sunset: '01/01/2020',
        deprecationReason: 'Create a Book instead'
    )]
    class Parchment
    {
        // ...
    }
}

// The value of the `sunset` attribute can be any string compatible with [the `\DateTime` constructor](https://www.php.net/manual/en/datetime.construct.php).
// It will be automatically converted to a valid HTTP date .
//
// It's also possible to set the `Sunset` header only for a specific [operation](operations.md):

namespace App\Entity {
    use ApiPlatform\Metadata\ApiResource;
    use ApiPlatform\Metadata\Get;

    #[ApiResource]
    #[Get(
        sunset: '01/01/2020',
        deprecationReason: 'Retrieve a Book instead'
    )]
    class Parchment
    {
        // ...
    }
}

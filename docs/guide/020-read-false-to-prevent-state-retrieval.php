
## Retrieving the Entity

If you want to bypass the automatic retrieval of the entity in your custom operation, you can set `read: false` in the
operation attribute:

[codeSelector]

```php
<?php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;
use App\Controller\CreateBookPublication;

#[ApiResource(operations: [
    new Get(),
    new Post(
        name: 'publication', 
        uriTemplate: '/books/{id}/publication', 
        controller: CreateBookPublication::class, 
        read: false
    )
])]
class Book
{
    // ...
}
```

```yaml
# api/config/api_platform/resources.yaml
App\Entity\Book:
    operations:
        ApiPlatform\Metadata\Get: ~
        post_publication:
            class: ApiPlatform\Metadata\Post
            uriTemplate: /books/{id}/publication
            controller: App\Controller\CreateBookPublication
            read: false
```

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<!-- api/config/api_platform/resources.xml -->

<resources xmlns="https://api-platform.com/schema/metadata/resources-3.0"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
        https://api-platform.com/schema/metadata/resources-3.0.xsd">
    <resource class="App\Entity\Book">
        <operations>
            <operation class="ApiPlatform\Metadata\Get" />
            <operation class="ApiPlatform\Metadata\Post" name="post_publication" uriTemplate="/books/{id}/publication"
                controller="App\Controller\CreateBookPublication" read="false" />
        </operations>
    </resource>
</resources>
```

[/codeSelector]

This way, it will skip the `ReadListener`. You can do the same for some other built-in listeners. See [Built-in Event Listeners](events.md#built-in-event-listeners)
for more information.

In your custom controller, the `__invoke()` method parameter should be called the same as the entity identifier.
So for the path `/user/{uuid}/bookmarks`, you must use `__invoke(string $uuid)`.


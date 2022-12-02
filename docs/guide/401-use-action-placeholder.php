
## Using the PlaceholderAction

Complex use cases may lead you to create multiple custom operations.

In such a case, you will probably create the same amount of custom controllers while you may not need to perform custom logic inside.

To avoid that, API Platform provides the `ApiPlatform\Action\PlaceholderAction` which behaves the same when using the [built-in operations](operations.md#operations).

You just need to set the `controller` attribute with this class. Here, the previous example updated:

[codeSelector]

```php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Action\PlaceholderAction;
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Get(),
    new Post(
        name: 'publication', 
        uriTemplate: '/books/{id}/publication', 
        controller: PlaceholderAction::class
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
            method: POST
            uriTemplate: /books/{id}/publication
            controller: ApiPlatform\Action\PlaceholderAction
```

```xml
<?xml version="1.0" encoding="UTF-8" ?>
<!-- api/config/api_platform/resources.xml -->

<resources
        xmlns="https://api-platform.com/schema/metadata/resources-3.0"
        xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
        xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
        https://api-platform.com/schema/metadata/resources-3.0.xsd">
    <resource class="App\Entity\Book">
        <operations>
            <operation class="ApiPlatform\Metadata\Get" />
            <operation class="ApiPlatform\Metadata\Post" name="post_publication" uriTemplate="/books/{id}/publication"
                       controller="ApiPlatform\Action\PlaceholderAction" />
        </operations>
    </resource>
</resources>
```

[/codeSelector]


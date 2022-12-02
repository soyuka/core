
## Alternative Method

There is another way to create a custom operation. However, we do not encourage its use. Indeed, this one disperses
the configuration at the same time in the routing and the resource configuration.

The `post_publication` operation references the Symfony route named `book_post_publication`.

Since version 2.3, you can also use the route name as operation name by convention, as shown in the following example
for `book_post_discontinuation` when neither `method` nor `routeName` attributes are specified.

First, let's create your resource configuration:

<CodeSelector>

```php
<?php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\Post;

#[ApiResource(operations: [
    new Get(),
    new Post(name: 'publication', routeName: 'book_post_publication'),
    new Post(name: 'book_post_discontinuation')
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
            routeName: book_post_publication
        book_post_discontinuation:
          class: ApiPlatform\Metadata\Post
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
            <operation class="ApiPlatform\Metadata\Post" name="post_publication" routeName="book_post_publication" />
            <operation class="ApiPlatform\Metadata\Post" name="book_post_discontinuation" />
        </operations>
    </resource>
</resources>
```

</CodeSelector>

API Platform will automatically map this `post_publication` operation to the route `book_post_publication`. Let's create a custom action
and its related route using annotations:

```php
<?php
// api/src/Controller/CreateBookPublication.php
namespace App\Controller;

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;
use Symfony\Component\Routing\Annotation\Route;

#[AsController]
class CreateBookPublication extends AbstractController
{
    public function __construct(
        private BookPublishingHandler $bookPublishingHandler
    ) {}

    #[Route(
        name: 'book_post_publication',
        path: '/books/{id}/publication',
        methods: ['POST'],
        defaults: [
            '_api_resource_class' => Book::class,
            '_api_operation_name' => '_api_/books/{id}/publication_post',
        ],
    )]
    public function __invoke(Book $book): Book
    {
        $this->bookPublishingHandler->handle($book);

        return $book;
    }
}
```

It is mandatory to set `_api_resource_class` and `_api_operation_name` in the parameters of the route (`defaults` key). It allows API Platform to work with the Symfony routing system.

Alternatively, you can also use a traditional Symfony controller and YAML or XML route declarations. The following example does
the same thing as the previous example:

```php
<?php
// api/src/Controller/BookController.php
namespace App\Controller;

use App\Entity\Book;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpKernel\Attribute\AsController;

#[AsController]
class BookController extends AbstractController
{
    public function createPublication(Book $book, BookPublishingHandler $bookPublishingHandler): Book
    {
        return $bookPublishingHandler->handle($book);
    }
}
```

```yaml
# api/config/routes.yaml
book_post_publication:
    path: /books/{id}/publication
    methods: ['POST']
    defaults:
        _controller: App\Controller\BookController::createPublication
        _api_resource_class: App\Entity\Book
        _api_item_operation_name: post_publication
```

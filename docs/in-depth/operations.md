# Operations

API Platform relies on the concept of operations. Operations can be applied to a resource exposed by the API. From
an implementation point of view, an operation is a link between a resource, a route and its related controller.

<p align="center" class="symfonycasts"><a href="https://symfonycasts.com/screencast/api-platform/operations?cid=apip"><img src="../distribution/images/symfonycasts-player.png" alt="Operations screencast"><br>Watch the Operations screencast</a></p>

API Platform automatically registers typical [CRUD](https://en.wikipedia.org/wiki/Create,_read,_update_and_delete) operations
and describes them in the exposed documentation (Hydra and Swagger). It also creates and registers routes corresponding
to these operations in the Symfony routing system (if it is available).

The behavior of built-in operations can be seen in the [Declare a Resource](/guide/declare-a-resource) guide.

The list of enabled operations can be configured on a per-resource basis. Creating custom operations on specific routes
is also possible. We see an Operation as a method, URI Template, type (dictionnary vs collection) tuple. 
Indeed the `ApiPlatform\Metadata\GetCollection` implements the `ApiPlatform\Metadata\CollectionOperationInterface`, meaning 
that it returns a collection.

When the `ApiPlatform\Metadata\ApiResource` attribute is applied to a PHP class, the following built-in CRUD
operations are automatically enabled:

Method   | Class                                | Mandatory* | Description
---------|--------------------------------------|------------|------------------------------
`GET`    | `ApiPlatform\Metadata\GetCollection` | yes        | Retrieve the (paginated) list of elements
`POST`   | `ApiPlatform\Metadata\Post`          | no         | Create a new element
`GET`    | `ApiPlatform\Metadata\Get`           | yes        | Retrieve an element
`PUT`    | `ApiPlatform\Metadata\Put`           | no         | Replace an element
`PATCH`  | `ApiPlatform\Metadata\Patch`         | no         | Apply a partial modification to an element
`DELETE` | `ApiPlatform\Metadata\Delete`        | no         | Delete an element

<sup>*</sup>These operations are mandatory as they play a role in [IRI](./uri) generation.


If no operation is specified, all default CRUD operations are automatically registered. It is also possible - and recommended
for large projects - to define operations explicitly.

Keep in mind that once you explicitly set up an operation, the automatically registered CRUD will no longer be.
If you declare even one operation manually, such as `#[ApiPlatform\Metadata\Get]`, you must declare the others manually as well if you need them.

Operations can be configured using annotations, XML or YAML. In the following examples, we enable only the built-in operation
read operations for both a collection of books and a single book endpoint. 

[codeSelector]

```php
<?php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;

#[ApiResource(operations: [
    new Get(),
    new GetCollection()
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
        ApiPlatform\Metadata\GetCollection: ~ # nothing more to add if we want to keep the default controller
        ApiPlatform\Metadata\Get: ~
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
            <operation class="ApiPlatform\Metadata\GetCollection" />
        </operations>
    </resource>
</resources>
```

[/codeSelector]


If you do not want to allow access to the resource item (i.e. you don't want a `GET` item operation), instead of omitting it altogether, you should instead declare a `GET` item operation which returns HTTP 404 (Not Found), so that the resource item can still be identified by an IRI. For example:

[codeSelector]

```php
<?php
// api/src/Entity/Book.php
namespace App\Entity;

use ApiPlatform\Action\NotFoundAction;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource(operations: [
    new Get(
        controller: NotFoundAction::class, 
        read: false, 
        output: false,
        openapi: false
    ),
    new GetCollection()
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
        ApiPlatform\Metadata\GetCollection: ~
        ApiPlatform\Metadata\Get:
            controller: ApiPlatform\Action\NotFoundAction
            read: false
            output: false
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
            <operation class="ApiPlatform\Metadata\GetCollection" />
            <operation class="ApiPlatform\Metadata\Get" controller="ApiPlatform\Action\NotFoundAction"
                       read="false" output="false" />
        </operations>
    </resource>
</resources>
```

[/codeSelector]
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: [], itemOperations: ['get', 'get_by_isbn' => ['method' => 'GET', 'path' => '/books/by_isbn/{isbn}.{_format}', 'requirements' => ['isbn' => '.+'], 'identifiers' => 'isbn']])]
class Book
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\Get;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[Get(operationName: 'get_by_isbn', path: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
#[Get]
class Book
{
}

?>

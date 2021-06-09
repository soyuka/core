<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Core\Annotation\ApiResource;

#[ApiResource(collectionOperations: ['get'], itemOperations: ['get' => ['controller' => NotFoundAction::class, 'read' => false, 'output' => false]])]
class DisableItemOperation
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\Get;
use ApiPlatform\Core\Action\NotFoundAction;
use ApiPlatform\Metadata\ApiResource;

#[ApiResource]
#[GetCollection]
#[Get(operationName: 'get', controller: NotFoundAction::class, read: false, output: false)]
class DisableItemOperation
{
}

?>
<?php

use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

// # Validation
// When processing the incoming request, when creating or updating content, API-Platform will validate the
// incoming content. It will use the [Symfony's validator](https://symfony.com/doc/current/validation.html).
//
// Validation is called when handling a POST, PATCH, PUT request as follows :

//graph LR
//Request --> Deserialization
//Deserialization --> Validation
//Validation --> Persister
//Persister --> Serialization
//Serialization --> Response

// ## Sequential Validation Groups
// If you need to specify the order in which your validation groups must be tested against, you can use a [group sequence](http://symfony.com/doc/current/validation/sequence_provider.html).

// ## Validate Delete Operations
// By default, validation is not triggered during a DELETE operation.
// If you need to do it for a particular use case, you can do it that way :
namespace App\Entity;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Delete;
use App\State\MyEntityRemoveProcessor;
use App\Validator\AssertCanDelete;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity]
#[ApiResource(
    operations: [
        new Delete(validationContext: ['groups' => ['deleteValidation']], processor: MyEntityRemoveProcessor::class),
        // This operation uses a Callable as group so that you can vary the Validation according to your dataset
        new Get(validationContext: ['groups' =>])
    ]
)]
#[AssertCanDelete(groups: ['deleteValidation'])]
class MyEntity
{
    #[ORM\Id, ORM\Column, ORM\GeneratedValue]
    private ?int $id = null;

    #[ORM\Column]
    public string $name = '';
}

namespace App\Validator;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

#[\Attribute]
class AssertCanDelete extends Constraint
{
    public string $message = 'The string "{{ string }}" contains an illegal character: it can only contain letters or numbers.';
    public string $mode = 'strict';
}

class AssertCanDeleteValidator extends ConstraintValidator
{
    public function validate(mixed $value, Constraint $constraint)
    {
        // TODO: Implement validate() method.
    }
}

namespace App\State;

use ApiPlatform\Doctrine\Common\State\RemoveProcessor as DoctrineRemoveProcessor;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Validator\ValidatorInterface;

class MyEntityRemoveProcessor implements ProcessorInterface
{
    public function __construct(
        private DoctrineRemoveProcessor $doctrineProcessor,
        private ValidatorInterface $validator,
    ) {
    }

    public function process($data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $this->validator->validate($data, ['groups' => ['deleteValidation']]);
        $this->doctrineProcessor->process($data, $operation, $uriVariables, $context);
    }
}

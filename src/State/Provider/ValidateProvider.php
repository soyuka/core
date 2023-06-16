<?php

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\Validator\ValidatorInterface;
use Symfony\Component\HttpFoundation\Response;

final class ValidateProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $inner, private readonly ValidatorInterface $validator)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $body = $this->inner->provide($operation, $uriVariables, $context);
        $request = $context['request'] ?? null;

        if (
            $body instanceof Response
            || $request?->isMethodSafe()
            || $request?->isMethod('DELETE')
        ) {
            return $body;
        }

        if (!($operation->canValidate() ?? false)) {
            return $body;
        }

        $this->validator->validate($body, $operation->getValidationContext() ?? []);

        return $body;
    }
}

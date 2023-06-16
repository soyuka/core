<?php

namespace ApiPlatform\Symfony\Security;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

final class AccessCheckerProvider implements ProviderInterface
{
    /**
     * @param ProviderInterface<mixed> $inner
     */
    public function __construct(
        private readonly ProviderInterface $inner,
        private readonly ?ResourceAccessCheckerInterface $resourceAccessChecker = null,
        private readonly ?string $event = null,
    ) {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        switch ($this->event) {
            case 'post_denormalize':
                $isGranted = $operation->getSecurityPostDenormalize();
                $message = $operation->getSecurityPostDenormalizeMessage();
                break;
            case 'post_validate':
                $isGranted = $operation->getSecurityPostValidation();
                $message = $operation->getSecurityPostValidationMessage();
                break;
            default:
                $isGranted = $operation->getSecurity();
                $message = $operation->getSecurityMessage();
        }


        if (null === $isGranted || !($request = $context['request'] ?? null)) {
            return $this->inner->provide($operation, $uriVariables, $context);;
        }

        $body = $this->inner->provide($operation, $uriVariables, $context);

        $resourceAccessCheckerContext = ['object' => $request->attributes->get('data'), 'previous_object' => $request->attributes->get('previous_data'), 'request' => $request];
        if (!$this->resourceAccessChecker->isGranted($operation->getClass(), $isGranted, $resourceAccessCheckerContext)) {
            throw new AccessDeniedException($message ?? 'Access Denied.');
        }

        return $body;
    }
}

<?php

/*
 * This file is part of the API Platform project.
 *
 * (c) Kévin Dunglas <dunglas@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace ApiPlatform\Symfony\Processor;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Serializer\OperationAwareSerializerContextBuilderInterface;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Serializer\Encoder\EncoderInterface;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\WebLink\GenericLinkProvider;
use Symfony\Component\WebLink\Link;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class SerializeProcessor implements ProcessorInterface
{
    public function __construct(private readonly ProcessorInterface $processor, private readonly SerializerInterface $serializer, private readonly OperationAwareSerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

        // $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        // if (!\count($resourcesToPush)) {
        //     return;
        // }
        //
        // $linkProvider = $request->attributes->get('_links', new GenericLinkProvider());
        // foreach ($resourcesToPush as $resourceToPush) {
        //     $linkProvider = $linkProvider->withLink((new Link('preload', $resourceToPush))->withAttribute('as', 'fetch'));
        // }
        // $request->attributes->set('_links', $linkProvider);

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !($operation?->canSerialize() ?? true)) {
            return $data;
        }

        $serializerContext = $this->serializerContextBuilder->createFromOperation($operation, normalization: true);
        if (isset($serializerContext['output']) && \array_key_exists('class', $serializerContext['output']) && null === $serializerContext['output']['class']) {
            return null;
        }

        // JSON: API related should not be here
        // if ($included = $request->attributes->get('_api_included')) {
        //     $context['api_included'] = $included;
        // }
        // why not context builder?
        // $resources = new ResourceList();
        // $context['resources'] = &$resources;
        // $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources';

        // $resourcesToPush = new ResourceList();
        // $context['resources_to_push'] = &$resourcesToPush;
        // $context[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources_to_push';
        // if (($options = $operation?->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
        //     $context['force_resource_class'] = $operation->getClass();
        // }

        $context['original_data'] = $data;
        return $this->processor->process($this->serializer->serialize($data, $context['request_format'], $serializerContext), $operation, $uriVariables, $context);
    }

    /**
     * Tries to serialize data that are not API resources (e.g. the entrypoint or data returned by a custom controller).
     *
     * @throws RuntimeException
     */
    // private function serializeRawData(ViewEvent $event, Request $request, $controllerResult): void
    // {
    //     if (\is_object($controllerResult)) {
    //         $event->setControllerResult($this->serializer->serialize($controllerResult, $request->getRequestFormat(), $request->attributes->get('_api_normalization_context', [])));
    //
    //         return;
    //     }
    //
    //     if (!$this->serializer instanceof EncoderInterface) {
    //         throw new RuntimeException(sprintf('The serializer must implement the "%s" interface.', EncoderInterface::class));
    //     }
    //
    //     $event->setControllerResult($this->serializer->encode($controllerResult, $request->getRequestFormat()));
    // }
}

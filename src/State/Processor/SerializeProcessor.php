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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProcessorInterface;
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
    public function __construct(private readonly ProcessorInterface $processor, private readonly SerializerInterface $serializer, private readonly SerializerContextBuilderInterface $serializerContextBuilder)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !($operation?->canSerialize() ?? true) || !($request = $context['request'] ?? null)) {
            return $data;
        }

        // @see ApiPlatform\State\Processor\RespondProcessor
        $context['original_data'] = $data;

        $serializerContext = $this->serializerContextBuilder->createFromRequest($request, normalization: true);
        if (isset($serializerContext['output']) && \array_key_exists('class', $serializerContext['output']) && null === $serializerContext['output']['class']) {
            return null;
        }

        // why not context builder?
        $resources = new ResourceList();
        $serializerContext['resources'] = &$resources;
        $serializerContext[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources';

        $resourcesToPush = new ResourceList();
        $serializerContext['resources_to_push'] = &$resourcesToPush;
        $serializerContext[AbstractObjectNormalizer::EXCLUDE_FROM_CACHE_KEY][] = 'resources_to_push';
        if (($options = $operation?->getStateOptions()) && $options instanceof Options && $options->getEntityClass()) {
            $serializerContext['force_resource_class'] = $operation->getClass();
        }

        if ($uriVariables) {
            $serializerContext['uri_variables'] = $uriVariables;
        }

        $serialized = $this->serializer->serialize($data, $request->getRequestFormat(), $serializerContext);
        $request->attributes->set('_resources', $request->attributes->get('_resources', []) + (array) $resources);
        if ($resourcesToPush) {
            $linkProvider = $request->attributes->get('_links', new GenericLinkProvider());
            foreach ($resourcesToPush as $resourceToPush) {
                $linkProvider = $linkProvider->withLink((new Link('preload', $resourceToPush))->withAttribute('as', 'fetch'));
            }
            $request->attributes->set('_links', $linkProvider);
        }

        return $this->processor->process($serialized, $operation, $uriVariables, $context);
    }
}

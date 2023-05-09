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
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
final class RespondProcessor implements ProcessorInterface
{
    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    public function __construct(private readonly ProcessorInterface $processor, private readonly RequestStack $requestStack)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        $data = $this->processor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Response) {
            return $data;
        }

        $request = $this->requestStack->getCurrentRequest();
        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $context['request_mime_type']),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        $status = $operation->getStatus();

        if ($sunset = $operation->getSunset()) {
            $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
        }

        if ($acceptPatch = $operation->getAcceptPatch()) {
            $headers['Accept-Patch'] = $acceptPatch;
        }

        $method = $request->getMethod();
        // if (
        //     $this->iriConverter
        //     && $operation
        //     && ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
        //     && 301 === $operation->getStatus()
        // ) {
        //     $status = 301;
        //     $headers['Location'] = $this->iriConverter->getIriFromResource($request->attributes->get('data'), UrlGeneratorInterface::ABS_PATH, $operation);
        // } elseif (HttpOperation::METHOD_PUT === $method && !($attributes['previous_data'] ?? null) && null === $status && ($operation instanceof Put && ($operation->getAllowCreate() ?? false))) {
        //     $status = Response::HTTP_CREATED;
        // }

        $status ??= self::METHOD_TO_CODE[$method] ?? Response::HTTP_OK;

        // if ($request->attributes->has('_api_write_item_iri')) {
        //     $headers['Content-Location'] = $request->attributes->get('_api_write_item_iri');
        //
        //     if ((Response::HTTP_CREATED === $status || (300 <= $status && $status < 400)) && HttpOperation::METHOD_POST === $method) {
        //         $headers['Location'] = $request->attributes->get('_api_write_item_iri');
        //     }
        // }

        return new Response(
            $data,
            $status,
            $headers
        );
    }
}

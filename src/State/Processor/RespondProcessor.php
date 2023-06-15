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

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Doctrine\Orm\State\Options;
use ApiPlatform\Exception\RuntimeException;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Put;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Metadata\ResourceClassResolverInterface;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\Serializer\ResourceList;
use ApiPlatform\Serializer\SerializerContextBuilderInterface;
use ApiPlatform\State\ProcessorInterface;
use ApiPlatform\Util\CloneTrait;
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
use ApiPlatform\Exception\InvalidArgumentException;

/**
 * Serializes data.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondProcessor implements ProcessorInterface
{
    use ClassInfoTrait;
    use CloneTrait;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    public function __construct(private readonly ResourceClassResolverInterface $resourceClassResolver, private IriConverterInterface $iriConverter)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($data instanceof Response || !$operation instanceof HttpOperation) {
            return $data;
        }

        if (!($request = $context['request'] ?? null)) {
            return $data;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
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

        if (
            $this->iriConverter
            && $operation
            && ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
            && 301 === $operation->getStatus()
        ) {
            $status = 301;
            $headers['Location'] = $this->iriConverter->getIriFromResource($data, UrlGeneratorInterface::ABS_PATH, $operation);
        } elseif ('PUT' === $method && !$request->attributes->get('previous_data') && null === $status && ($operation instanceof Put && ($operation->getAllowCreate() ?? false))) {
            $status = Response::HTTP_CREATED;
        }

        $status ??= self::METHOD_TO_CODE[$method] ?? Response::HTTP_OK;

        $originalData = $context['original_data'] ?? null;
        if ($originalData && is_object($originalData) && $this->resourceClassResolver->isResourceClass($this->getObjectClass($originalData))) {
            $iri = $this->iriConverter->getIriFromResource($originalData);
            $headers['Content-Location'] = $iri;

            if ((201 === $status || (300 <= $status && $status < 400)) && 'POST' === $method) {
                $headers['Location'] = $iri;
            }
        }

        return new Response(
            $data,
            $status,
            $headers
        );
    }
}

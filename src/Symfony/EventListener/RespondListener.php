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

namespace ApiPlatform\Symfony\EventListener;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\Api\UrlGeneratorInterface;
use ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResources;
use ApiPlatform\Util\OperationRequestInitiatorTrait;
use ApiPlatform\Util\RequestAttributesExtractor;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Event\ViewEvent;
use Symfony\Component\Marshaller\Context\Option\PropertyNameFormatterOption;
use Symfony\Component\Marshaller\Context\Option\PropertyTypeOption;
use Symfony\Component\Marshaller\Context\Option\PropertyValueFormatterOption;
use Symfony\Component\Marshaller\MarshallerInterface;
use Symfony\Component\Marshaller\Output\OutputStreamOutput;
use Symfony\Component\Marshaller\Context\Context;
use Symfony\Component\Marshaller\Context\Option\TypeOption;
use Symfony\Component\Marshaller\Context\Option\HooksOption;
use Symfony\Component\Marshaller\Context\Option\ValueFormatterOption;
use ApiPlatform\Tests\Fixtures\TestBundle\Entity\AttributeResource;
use ApiPlatform\Hydra\Collection;
use function Symfony\Component\Marshaller\marshal_generate;

/**
 * Builds the response object.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class RespondListener
{
    use OperationRequestInitiatorTrait;

    public const METHOD_TO_CODE = [
        'POST' => Response::HTTP_CREATED,
        'DELETE' => Response::HTTP_NO_CONTENT,
    ];

    public function __construct(ResourceMetadataCollectionFactoryInterface $resourceMetadataFactory = null, private readonly ?IriConverterInterface $iriConverter = null, private readonly ?MarshallerInterface $marshaller = null)
    {
        $this->resourceMetadataCollectionFactory = $resourceMetadataFactory;
    }

    /**
     * Creates a Response to send to the client according to the requested format.
     */
    public function onKernelView(ViewEvent $event): void
    {
        $controllerResult = $event->getControllerResult();
        $request = $event->getRequest();
        $operation = $this->initializeOperation($request);

        $attributes = RequestAttributesExtractor::extractAttributes($request);

        if ($controllerResult instanceof Response && ($attributes['respond'] ?? false)) {
            $event->setResponse($controllerResult);

            return;
        }

        if ($controllerResult instanceof Response || !($attributes['respond'] ?? $request->attributes->getBoolean('_api_respond'))) {
            return;
        }

        $headers = [
            'Content-Type' => sprintf('%s; charset=utf-8', $request->getMimeType($request->getRequestFormat())),
            'Vary' => 'Accept',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'deny',
        ];

        $status = $operation?->getStatus();

        if ($sunset = $operation?->getSunset()) {
            $headers['Sunset'] = (new \DateTimeImmutable($sunset))->format(\DateTime::RFC1123);
        }

        if ($acceptPatch = $operation?->getAcceptPatch()) {
            $headers['Accept-Patch'] = $acceptPatch;
        }

        if (
            $this->iriConverter &&
            $operation &&
            ($operation->getExtraProperties()['is_alternate_resource_metadata'] ?? false)
            && 301 === $operation->getStatus()
        ) {
            $status = 301;
            $headers['Location'] = $this->iriConverter->getIriFromResource($request->attributes->get('data'), UrlGeneratorInterface::ABS_PATH, $operation);
        }

        $status ??= self::METHOD_TO_CODE[$request->getMethod()] ?? Response::HTTP_OK;

        if ($request->attributes->has('_api_write_item_iri')) {
            $headers['Content-Location'] = $request->attributes->get('_api_write_item_iri');

            if ((Response::HTTP_CREATED === $status || (300 <= $status && $status < 400)) && $request->isMethod('POST')) {
                $headers['Location'] = $request->attributes->get('_api_write_item_iri');
            }
        }

        $valueFormatterOption = new PropertyValueFormatterOption([
            AttributeResource::class => ['identifier' => $this->test(...)]
        ]);

        $nameFormatterOption = new PropertyNameFormatterOption([
            AttributeResource::class => ['identifier' => fn() => '@id']
        ]);

        $propertyTypeOption = new PropertyTypeOption([
            Collection::class => ['collection' => sprintf('array<int, %s>', AttributeResource::class)]
        ]);

        // $propertyTypeOption = new ExtendTypeOption([
        //     AttributeResource::class => 'Item<AttributeResource>'
        // ]);

        $context = new Context($nameFormatterOption, $valueFormatterOption, $propertyTypeOption);
        $response = new StreamedResponse(
            function () use ($controllerResult, $context) {
                $this->marshaller->marshal($controllerResult, 'json', new OutputStreamOutput(), $context);
            },
            $status,
            $headers
        );

        $event->setResponse($response);
    }

    public function test(int $value, array $context): string
    {
        return sprintf('/foo/bar/%d', $value);
    }
}

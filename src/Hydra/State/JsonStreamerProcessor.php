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

namespace ApiPlatform\Hydra\State;

use ApiPlatform\Hydra\Collection;
use ApiPlatform\Hydra\IriTemplate;
use ApiPlatform\Hydra\IriTemplateMapping;
use ApiPlatform\Metadata\CollectionOperationInterface;
use ApiPlatform\Metadata\Error;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\Metadata\QueryParameterInterface;
use ApiPlatform\State\Pagination\PaginatorInterface;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\JsonStreamer\StreamWriterInterface;
use Symfony\Component\TypeInfo\Type;

final class JsonStreamerProcessor implements ProcessorInterface
{
    public function __construct(
        private readonly ProcessorInterface $processor,
        private readonly StreamWriterInterface $jsonStreamer,
    ) {
    }

    /**
     * @param FilterInterface[]   $filters (This parameter is now unused in the function body)
     * @param array{path: string} $parts
     */
    private function getSearch(string $resourceClass, array $parts, array $filters, ?Parameters $parameters, string $hydraPrefix): IriTemplate
    {
        $variables = [];
        /** @var list<IriTemplateMapping> */
        $mapping = [];

        foreach ($parameters ?? [] as $key => $parameter) {
            if (!$parameter instanceof QueryParameterInterface || false === $parameter->getHydra()) {
                continue;
            }

            if (!($property = $parameter->getProperty())) {
                continue;
            }

            $variables[] = $key;
            $m = new IriTemplateMapping(
                variable: $key,
                property: $property,
                required: $parameter->getRequired() ?? false
            );
            $mapping[] = $m;
        }

        return new IriTemplate(
            template: \sprintf('%s{?%s}', $parts['path'], implode(',', $variables)),
            variableRepresentation: 'BasicRepresentation',
            mapping: $mapping
        );
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if ($operation instanceof Error || $data instanceof Response) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        if ($operation instanceof CollectionOperationInterface) {
            $collection = new Collection();
            $collection->member = iterator_to_array($data);
            // $collection->search = $this->getSearch();
            if ($data instanceof PaginatorInterface) {
                $collection->totalItems = $data->getTotalItems();
            }

            $response = new StreamedResponse($this->jsonStreamer->write($collection, Type::object(Collection::class), [
                'data' => $data,
                'operation' => $operation,
            ]));
        } else {
            $response = new StreamedResponse($this->jsonStreamer->write($data, Type::object($operation->getClass()), [
                'data' => $data,
                'operation' => $operation,
            ]));
        }

        return $this->processor->process($response, $operation, $uriVariables, $context);
    }
}

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

namespace ApiPlatform\State\Provider;

use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameters;
use ApiPlatform\State\Exception\ParameterNotSupportedException;
use ApiPlatform\State\Exception\ProviderNotFoundException;
use ApiPlatform\State\ParameterNotFound;
use ApiPlatform\State\ParameterProvider\ReadLinkParameterProvider;
use ApiPlatform\State\ProviderInterface;
use ApiPlatform\State\Util\ParameterParserTrait;
use ApiPlatform\State\Util\RequestParser;
use Psr\Container\ContainerInterface;

/**
 * Loops over parameters to:
 *   - compute its values set as extra properties from the Parameter object (`_api_values`)
 *   - call the Parameter::provider if any and updates the Operation
 *
 * @experimental
 */
final class ParameterProvider implements ProviderInterface
{
    use ParameterParserTrait;

    public function __construct(private readonly ?ProviderInterface $decorated = null, private readonly ?ContainerInterface $locator = null)
    {
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $request = $context['request'] ?? null;
        if ($request && null === $request->attributes->get('_api_query_parameters')) {
            $queryString = RequestParser::getQueryString($request);
            $request->attributes->set('_api_query_parameters', $queryString ? RequestParser::parseRequestParams($queryString) : []);
        }

        if ($request && null === $request->attributes->get('_api_header_parameters')) {
            $request->attributes->set('_api_header_parameters', $request->headers->all());
        }

        if ($request && null === $request->attributes->get('_api_path_parameters')) {
            $request->attributes->set('_api_path_parameters', $request->attributes->all());
        }

        $parameters = $operation->getParameters() ?? new Parameters();

        if ($operation instanceof HttpOperation) {
            // TODO: this should return Parameters but its a BC, prepare that change in 4.3
            foreach ($operation->getUriVariables() ?? [] as $key => $uriVariable) {
                if ($uriVariable->getSecurity() && !$uriVariable->getProvider()) {
                    $uriVariable = $uriVariable->withProvider(ReadLinkParameterProvider::class);
                }

                $parameters->add($key, $uriVariable->withKey($key));
            }

            if (true === $operation->getStrictQueryParameterValidation()) {
                $keys = [];
                foreach ($parameters as $parameter) {
                    $keys[] = $parameter->getKey();
                }

                foreach (array_keys($request->attributes->get('_api_query_parameters')) as $key) {
                    if (!\in_array($key, $keys, true)) {
                        throw new ParameterNotSupportedException($key);
                    }
                }
            }
        }

        foreach ($parameters as $parameter) {
            // we force API Platform's value extraction, use _api_query_parameters or _api_header_parameters if you need to set a value
            if (isset($parameter->getExtraProperties()['_api_values'])) {
                unset($parameter->getExtraProperties()['_api_values']);
            }

            $context = ['operation' => $operation] + $context;
            $values = $this->getParameterValues($parameter, $request, $context);
            $value = $this->extractParameterValues($parameter, $values);

            if (($default = $parameter->getSchema()['default'] ?? false) && ($value instanceof ParameterNotFound || !$value)) {
                $value = $default;
            }

            $parameter->setValue($value);

            if ($value instanceof ParameterNotFound) {
                continue;
            }

            if (null === ($provider = $parameter->getProvider())) {
                continue;
            }

            if (\is_callable($provider)) {
                if (($op = $provider($parameter, $values, $context)) instanceof Operation) {
                    $operation = $op;
                }

                continue;
            }

            if (\is_string($provider)) {
                if (!$this->locator->has($provider)) {
                    throw new ProviderNotFoundException(\sprintf('Provider "%s" not found on operation "%s"', $provider, $operation->getName()));
                }

                $provider = $this->locator->get($provider);
            }

            if (($op = $provider->provide($parameter, $values, $context)) instanceof Operation) {
                $operation = $op;
            }
        }

        if (\count($parameters)) {
            $operation = $operation->withParameters($parameters);
        }
        $request?->attributes->set('_api_operation', $operation);
        $context['operation'] = $operation;

        return $this->decorated?->provide($operation, $uriVariables, $context);
    }
}

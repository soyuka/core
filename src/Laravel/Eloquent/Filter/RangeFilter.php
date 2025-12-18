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

namespace ApiPlatform\Laravel\Eloquent\Filter;

use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The range filter allows you to filter by a value lower than, greater than, lower than or equal, greater than or equal and between two values.
 *
 * Syntax: `?property[<lt|gt|lte|gte|between>]=value`.
 */
final class RangeFilter implements FilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use QueryPropertyTrait;

    private const PARAMETER_BETWEEN = 'between';
    private const PARAMETER_GREATER_THAN = 'gt';
    private const PARAMETER_GREATER_THAN_OR_EQUAL = 'gte';
    private const PARAMETER_LESS_THAN = 'lt';
    private const PARAMETER_LESS_THAN_OR_EQUAL = 'lte';

    private const OPERATOR_VALUE = [
        self::PARAMETER_LESS_THAN => '<',
        self::PARAMETER_GREATER_THAN => '>',
        self::PARAMETER_LESS_THAN_OR_EQUAL => '<=',
        self::PARAMETER_GREATER_THAN_OR_EQUAL => '>=',
    ];

    /**
     * @param Builder<Model>       $builder
     * @param array<string, mixed> $context
     */
    public function apply(Builder $builder, mixed $values, Parameter $parameter, array $context = []): Builder
    {
        if (!\is_array($values)) {
            return $builder;
        }

        $queryProperty = $this->getQueryProperty($parameter);

        foreach ($values as $operator => $value) {
            if (self::PARAMETER_BETWEEN === $operator) {
                $rangeValue = explode('..', $value, 2);

                if (2 !== \count($rangeValue)) {
                    continue;
                }

                if (!is_numeric($rangeValue[0]) || !is_numeric($rangeValue[1])) {
                    continue;
                }

                $rangeValue = [$rangeValue[0] + 0, $rangeValue[1] + 0];

                if ($rangeValue[0] === $rangeValue[1]) {
                    $builder = $builder->{$context['whereClause'] ?? 'where'}($queryProperty, '=', $rangeValue[0]);
                    continue;
                }

                $builder = $builder->{$context['whereClause'] ?? 'where'}($queryProperty, '>=', $rangeValue[0])
                    ->{$context['whereClause'] ?? 'where'}($queryProperty, '<=', $rangeValue[1]);
                continue;
            }

            if (isset(self::OPERATOR_VALUE[$operator])) {
                if (!is_numeric($value)) {
                    continue;
                }

                $builder = $builder->{$context['whereClause'] ?? 'where'}($queryProperty, self::OPERATOR_VALUE[$operator], $value + 0);
            }
        }

        return $builder;
    }

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'number'];
    }

    /**
     * @return OpenApiParameter[]
     */
    public function getOpenApiParameters(Parameter $parameter): array
    {
        $in = $parameter instanceof QueryParameter ? 'query' : 'header';
        $key = $parameter->getKey();

        return [
            new OpenApiParameter(name: $key.'[gt]', in: $in),
            new OpenApiParameter(name: $key.'[lt]', in: $in),
            new OpenApiParameter(name: $key.'[gte]', in: $in),
            new OpenApiParameter(name: $key.'[lte]', in: $in),
            new OpenApiParameter(name: $key.'[between]', in: $in),
        ];
    }
}

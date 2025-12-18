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

namespace ApiPlatform\Doctrine\Odm\Filter;

use ApiPlatform\Doctrine\Common\Filter\RangeFilterInterface;
use ApiPlatform\Metadata\BackwardCompatibleFilterDescriptionTrait;
use ApiPlatform\Metadata\JsonSchemaFilterInterface;
use ApiPlatform\Metadata\OpenApiParameterFilterInterface;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Parameter;
use ApiPlatform\Metadata\QueryParameter;
use ApiPlatform\OpenApi\Model\Parameter as OpenApiParameter;
use Doctrine\ODM\MongoDB\Aggregation\Builder;

/**
 * The range filter allows you to filter by a value lower than, greater than, lower than or equal, greater than or equal and between two values.
 *
 * Syntax: `?property[<lt|gt|lte|gte|between>]=value`.
 *
 * <div data-code-selector>
 *
 * ```php
 * <?php
 * // api/src/Entity/Book.php
 * use ApiPlatform\Metadata\ApiResource;
 * use ApiPlatform\Metadata\GetCollection;
 * use ApiPlatform\Metadata\QueryParameter;
 * use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
 *
 * #[ApiResource]
 * #[GetCollection(
 *     parameters: [
 *         'price' => new QueryParameter(filter: new RangeFilter())
 *     ]
 * )]
 * class Book
 * {
 *     // ...
 * }
 * ```
 *
 * ```yaml
 * # config/services.yaml
 * services:
 *     book.range_filter:
 *         parent: 'api_platform.doctrine.odm.range_filter'
 *         arguments: [ { price: ~ } ]
 *         tags:  [ 'api_platform.filter' ]
 *         # The following are mandatory only if a _defaults section is defined with inverted values.
 *         # You may want to isolate filters in a dedicated file to avoid adding the following lines (by adding them in the defaults section)
 *         autowire: false
 *         autoconfigure: false
 *         public: false
 *
 * # api/config/api_platform/resources.yaml
 * resources:
 *     App\Entity\Book:
 *         - operations:
 *               ApiPlatform\Metadata\GetCollection:
 *                   filters: ['book.range_filter']
 * ```
 *
 * ```xml
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <!-- api/config/services.xml -->
 * <?xml version="1.0" encoding="UTF-8" ?>
 * <container
 *         xmlns="http://symfony.com/schema/dic/services"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="http://symfony.com/schema/dic/services
 *         https://symfony.com/schema/dic/services/services-1.0.xsd">
 *     <services>
 *         <service id="book.range_filter" parent="api_platform.doctrine.odm.range_filter">
 *             <argument type="collection">
 *                 <argument key="price"/>
 *             </argument>
 *             <tag name="api_platform.filter"/>
 *         </service>
 *     </services>
 * </container>
 * <!-- api/config/api_platform/resources.xml -->
 * <resources
 *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
 *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
 *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
 *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
 *     <resource class="App\Entity\Book">
 *         <operations>
 *             <operation class="ApiPlatform\Metadata\GetCollection">
 *                 <filters>
 *                     <filter>book.range_filter</filter>
 *                 </filters>
 *             </operation>
 *         </operations>
 *     </resource>
 * </resources>
 * ```
 *
 * </div>
 *
 * Given that the collection endpoint is `/books`, you can filter books with the following query: `/books?price[between]=12.99..15.99`.
 *
 * @author Lee Siong Chan <ahlee2326@me.com>
 * @author Alan Poulain <contact@alanpoulain.eu>
 */
final class RangeFilter implements FilterInterface, RangeFilterInterface, JsonSchemaFilterInterface, OpenApiParameterFilterInterface
{
    use BackwardCompatibleFilterDescriptionTrait;

    public function apply(Builder $aggregationBuilder, string $resourceClass, ?Operation $operation = null, array $context = []): void
    {
        $parameter = $context['parameter'] ?? null;
        if (!$parameter) {
            return;
        }

        $values = $parameter->getValue();
        if (!\is_array($values)) {
            return;
        }

        $property = $parameter->getProperty();

        foreach ($values as $operator => $value) {
            $this->addMatch($aggregationBuilder, $property, $operator, $value);
        }
    }

    private function addMatch(Builder $aggregationBuilder, string $field, string $operator, string $value): void
    {
        switch ($operator) {
            case self::PARAMETER_BETWEEN:
                $rangeValue = explode('..', $value, 2);

                if (2 !== \count($rangeValue)) {
                    return;
                }

                if (!is_numeric($rangeValue[0]) || !is_numeric($rangeValue[1])) {
                    return;
                }

                $rangeValue = [$rangeValue[0] + 0, $rangeValue[1] + 0];

                if ($rangeValue[0] === $rangeValue[1]) {
                    $aggregationBuilder->match()->field($field)->equals($rangeValue[0]);

                    return;
                }

                $aggregationBuilder->match()->field($field)->gte($rangeValue[0])->lte($rangeValue[1]);

                break;
            case self::PARAMETER_GREATER_THAN:
                if (!is_numeric($value)) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->gt($value + 0);

                break;
            case self::PARAMETER_GREATER_THAN_OR_EQUAL:
                if (!is_numeric($value)) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->gte($value + 0);

                break;
            case self::PARAMETER_LESS_THAN:
                if (!is_numeric($value)) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->lt($value + 0);

                break;
            case self::PARAMETER_LESS_THAN_OR_EQUAL:
                if (!is_numeric($value)) {
                    return;
                }

                $aggregationBuilder->match()->field($field)->lte($value + 0);

                break;
        }
    }

    public function getSchema(Parameter $parameter): array
    {
        return ['type' => 'number'];
    }

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

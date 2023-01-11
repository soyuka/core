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

namespace ApiPlatform\Metadata;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::IS_REPEATABLE)]
final class GetCollection extends HttpOperation implements CollectionOperationInterface
{
    private $itemUriTemplate;

    /**
     * {@inheritdoc}
     */
    public function __construct(
        ?string $uriTemplate = null,
        ?array $types = null,
        $formats = null,
        $inputFormats = null,
        $outputFormats = null,
        $uriVariables = null,
        ?string $routePrefix = null,
        ?string $routeName = null,
        ?array $defaults = null,
        ?array $requirements = null,
        ?array $options = null,
        ?bool $stateless = null,
        ?string $sunset = null,
        ?string $acceptPatch = null,
        $status = null,
        ?string $host = null,
        ?array $schemes = null,
        ?string $condition = null,
        ?string $controller = null,
        ?array $cacheHeaders = null,

        ?array $hydraContext = null,
        ?array $openapiContext = null,
        ?bool $openapi = null,
        ?array $exceptionToStatus = null,

        ?bool $queryParameterValidationEnabled = null,

        ?string $shortName = null,
        ?string $class = null,
        /**
         * The `paginationEnabled` option enables (or disables) the pagination for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationEnabled=true)] // Enabled
         * #[GetCollection(paginationEnabled=false)] // Disabled
         * ```
         */
        ?bool $paginationEnabled = null,
        ?string $paginationType = null,
        /**
         * The `paginationItemsPerPage` option defines the number of items per page for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationItemsPerPage=50)]
         * ```
         */
        ?int $paginationItemsPerPage = null,
        /**
         * The `paginationMaximumItemsPerPage` option defines the maximum number of items per page for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationMaximumItemsPerPage=60)]
         * ```
         */
        ?int $paginationMaximumItemsPerPage = null,
        /**
         * The `paginationPartial` option enables (or disables) the partial pagination for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationPartial=true)] // Enabled
         * #[GetCollection(paginationPartial=false)] // Disabled
         * ```
         */
        ?bool $paginationPartial = null,
        /**
         * The `paginationClientEnabled` option allows (or disallows) the client to enable (or disable) the pagination for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationClientEnabled=true)] // Enabled
         * #[GetCollection(paginationClientEnabled=false)] // Disabled
         * ```
         *
         * The pagination can now be enabled (or disabled) by adding a query parameter named `pagination`:
         * - `GET /books?pagination=false`: disabled
         * - `GET /books?pagination=true`: enabled
         */
        ?bool $paginationClientEnabled = null,
        /**
         * The `paginationClientItemsPerPage` option allows (or disallows) the client to set the number of items per page for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationClientItemsPerPage=true)] // Enabled
         * #[GetCollection(paginationClientItemsPerPage=false)] // Disabled
         * ```
         *
         * The number of items can now be set by adding a query parameter named `itemsPerPage`:
         * - `GET /books?itemsPerPage=50`
         */
        ?bool $paginationClientItemsPerPage = null,
        /**
         * The `paginationClientPartial` option allows (or disallows) the client to enable (or disable) the partial pagination for the current collection operation.
         * It overrides the resource and global configurations.
         *
         * ```php
         * #[GetCollection(paginationClientPartial=true)] // Enabled
         * #[GetCollection(paginationClientPartial=false)] // Disabled
         * ```
         *
         * The partial pagination can now be enabled (or disabled) by adding a query parameter named `partial`:
         * - `GET /books?partial=false`: disabled
         * - `GET /books?partial=true`: enabled
         */
        ?bool $paginationClientPartial = null,
        /**
         * The PaginationExtension of API Platform performs some checks on the `QueryBuilder` to guess, in most common
         * cases, the correct values to use when configuring the Doctrine ORM Paginator:
         * - `$fetchJoinCollection` argument: Whether there is a join to a collection-valued association.
         *   When set to `true`, the Doctrine ORM Paginator will perform an additional query, in order to get the
         *   correct number of results. You can configure this using the `paginationFetchJoinCollection` option:
         *   ```php
         *   #[GetCollection(paginationFetchJoinCollection: false)]
         *   ```
         * - `setUseOutputWalkers` setter: Whether to use output walkers.
         *   When set to `true`, the Doctrine ORM Paginator will use output walkers, which are compulsory for some types
         *   of queries. You can configure this using the `paginationUseOutputWalkers` option:
         *   ```php
         *   #[GetCollection(paginationUseOutputWalkers: false)]
         *   ```
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        ?bool $paginationFetchJoinCollection = null,
        ?bool $paginationUseOutputWalkers = null,
        /**
         * The `paginationViaCursor` option configures the cursor-based pagination for the current resource.
         * It overrides the resource and global configurations.
         *
         * Select your unique sorted field as well as the direction you'll like the pagination to go via filters:
         *
         * ```php
         * #[GetCollection(
         *     paginationViaCursor: [
         *         ['field' => 'id', 'direction' => 'DESC']
         *     ],
         *     paginationPartial: true
         * )]
         * ```
         *
         * Note that for now you have to declare a `RangeFilter` and an `OrderFilter` on the property used for the cursor-based pagination:
         *
         * ```php
         * use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
         * use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
         *
         * #[ApiFilter(RangeFilter::class, properties: ["id"])]
         * #[ApiFilter(OrderFilter::class, properties: ["id" => "DESC"])]
         * ```
         *
         * To know more about cursor-based pagination take a look at [this blog post on medium (draft)](https://medium.com/@sroze/74fd1d324723).
         */
        ?array $paginationViaCursor = null,
        ?array $order = null,
        ?string $description = null,
        ?array $normalizationContext = null,
        ?array $denormalizationContext = null,
        ?string $security = null,
        ?string $securityMessage = null,
        ?string $securityPostDenormalize = null,
        ?string $securityPostDenormalizeMessage = null,
        ?string $securityPostValidation = null,
        ?string $securityPostValidationMessage = null,
        ?string $deprecationReason = null,
        ?array $filters = null,
        ?array $validationContext = null,
        $input = null,
        $output = null,
        $mercure = null,
        $messenger = null,
        ?bool $elasticsearch = null,
        ?int $urlGenerationStrategy = null,
        ?bool $read = null,
        ?bool $deserialize = null,
        ?bool $validate = null,
        ?bool $write = null,
        ?bool $serialize = null,
        ?bool $fetchPartial = null,
        ?bool $forceEager = null,
        ?int $priority = null,
        ?string $name = null,
        $provider = null,
        $processor = null,
        array $extraProperties = [],
        ?string $itemUriTemplate = null
    ) {
        parent::__construct(self::METHOD_GET, ...\func_get_args());
        $this->itemUriTemplate = $itemUriTemplate;
    }

    public function getItemUriTemplate(): ?string
    {
        return $this->itemUriTemplate;
    }

    public function withItemUriTemplate(string $itemUriTemplate): self
    {
        $self = clone $this;
        $self->itemUriTemplate = $itemUriTemplate;

        return $self;
    }
}

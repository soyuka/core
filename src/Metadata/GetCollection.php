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
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationEnabled: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationEnabled: true
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationEnabled=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         */
        ?bool $paginationEnabled = null,
        /**
         * The `paginationType` option defines the type of pagination (`page` or `cursor`) to use for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationType: 'page')]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationType: page
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationType="page" />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         */
        ?string $paginationType = null,
        /**
         * The `paginationItemsPerPage` option defines the number of items per page for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationItemsPerPage: 30)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationItemsPerPage: 30
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationItemsPerPage=30 />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         */
        ?int $paginationItemsPerPage = null,
        /**
         * The `paginationMaximumItemsPerPage` option defines the maximum number of items per page for the current resource.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationMaximumItemsPerPage: 50)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationMaximumItemsPerPage: 50
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationMaximumItemsPerPage=50 />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         */
        ?int $paginationMaximumItemsPerPage = null,
        /**
         * The `paginationPartial` option enables (or disables) the partial pagination for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationPartial: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationPartial: true
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationPartial=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         */
        ?bool $paginationPartial = null,
        /**
         * The `paginationClientEnabled` option allows (or disallows) the client to enable (or disable) the pagination for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientEnabled: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationClientEnabled: true
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientEnabled=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         *
         * The pagination can now be enabled (or disabled) by adding a query parameter named `pagination`:
         * - `GET /books?pagination=false`: disabled
         * - `GET /books?pagination=true`: enabled
         */
        ?bool $paginationClientEnabled = null,
        /**
         * The `paginationClientItemsPerPage` option allows (or disallows) the client to set the number of items per page for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientItemsPerPage: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationClientItemsPerPage: true
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientItemsPerPage=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         *
         * The number of items can now be set by adding a query parameter named `itemsPerPage`:
         * - `GET /books?itemsPerPage=50`
         */
        ?bool $paginationClientItemsPerPage = null,
        /**
         * The `paginationClientPartial` option allows (or disallows) the client to enable (or disable) the partial pagination for the current collection operation.
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationClientPartial: true)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationClientPartial: true
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationClientPartial=true />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         *
         * The partial pagination can now be enabled (or disabled) by adding a query parameter named `partial`:
         * - `GET /books?partial=false`: disabled
         * - `GET /books?partial=true`: enabled
         */
        ?bool $paginationClientPartial = null,
        /**
         * The PaginationExtension of API Platform performs some checks on the `QueryBuilder` to guess, in most common
         * cases, the correct values to use when configuring the Doctrine ORM Paginator: `$fetchJoinCollection`
         * argument, whether there is a join to a collection-valued association.
         *
         * When set to `true`, the Doctrine ORM Paginator will perform an additional query, in order to get the
         * correct number of results. You can configure this using the `paginationFetchJoinCollection` option:
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationFetchJoinCollection: false)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationFetchJoinCollection: false
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationFetchJoinCollection=false />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        ?bool $paginationFetchJoinCollection = null,
        /**
         * The PaginationExtension of API Platform performs some checks on the `QueryBuilder` to guess, in most common
         * cases, the correct values to use when configuring the Doctrine ORM Paginator: `$setUseOutputWalkers` setter,
         * whether to use output walkers.
         *
         * When set to `true`, the Doctrine ORM Paginator will use output walkers, which are compulsory for some types
         * of queries. You can configure this using the `paginationUseOutputWalkers` option:
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\GetCollection;
         *
         * #[GetCollection(paginationUseOutputWalkers: false)]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationUseOutputWalkers: false
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationUseOutputWalkers=false />
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
         *
         * For more information, please see the [Pagination](https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html) entry in the Doctrine ORM documentation.
         */
        ?bool $paginationUseOutputWalkers = null,
        /**
         * The `paginationViaCursor` option configures the cursor-based pagination for the current resource.
         * Select your unique sorted field as well as the direction you'll like the pagination to go via filters.
         * Note that for now you have to declare a `RangeFilter` and an `OrderFilter` on the property used for the cursor-based pagination:
         *
         * [codeSelector]
         * ```php
         * <?php
         * // api/src/Entity/Book.php
         * use ApiPlatform\Metadata\ApiFilter;
         * use ApiPlatform\Metadata\GetCollection;
         * use ApiPlatform\Doctrine\Odm\Filter\OrderFilter;
         * use ApiPlatform\Doctrine\Odm\Filter\RangeFilter;
         *
         * #[GetCollection(paginationPartial: true, paginationViaCursor: [['field' => 'id', 'direction' => 'DESC']])]
         * #[ApiFilter(RangeFilter::class, properties: ["id"])]
         * #[ApiFilter(OrderFilter::class, properties: ["id" => "DESC"])]
         * class Book
         * {
         *     // ...
         * }
         * ```
         * ```yaml
         * # api/config/api_platform/resources.yaml
         * App\Entity\Book:
         *     operations:
         *         ApiPlatform\Metadata\GetCollection:
         *             paginationPartial: true
         *             paginationViaCursor:
         *                 - { field: 'id', direction: 'DESC' }
         *             filters: [ 'app.filters.book.range', 'app.filters.book.order' ]
         * ```
         * ```xml
         * <?xml version="1.0" encoding="UTF-8" ?>
         * <!-- api/config/api_platform/resources.xml -->
         *
         * <resources
         *         xmlns="https://api-platform.com/schema/metadata/resources-3.0"
         *         xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         *         xsi:schemaLocation="https://api-platform.com/schema/metadata/resources-3.0
         *         https://api-platform.com/schema/metadata/resources-3.0.xsd">
         *     <resource class="App\Entity\Book">
         *         <operations>
         *             <operation class="ApiPlatform\Metadata\GetCollection" paginationPartial=true>
         *                 <filters>
         *                     <filter>app.filters.book.range</filter>
         *                     <filter>app.filters.book.order</filter>
         *                 </filters>
         *                 <paginationViaCursor>
         *                     <paginationField field="id" direction="DESC" />
         *                 </paginationViaCursor>
         *             </operation>
         *         </operations>
         *     </resource>
         * </resources>
         * ```
         * [/codeSelector]
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

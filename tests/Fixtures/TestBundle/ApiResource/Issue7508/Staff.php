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

namespace ApiPlatform\Tests\Fixtures\TestBundle\ApiResource\Issue7508;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\GetCollection;
use ApiPlatform\Metadata\HeaderParameter;
use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ParameterProvider\IriConverterParameterProvider;

#[ApiResource(
    shortName: 'Issue7508Staff',
    operations: [
        new GetCollection(
            uriTemplate: '/issue7508_staffs',
            parameters: [
                new HeaderParameter(
                    key: 'organization',
                    provider: IriConverterParameterProvider::class,
                    extraProperties: [
                        'resource_class' => Organization::class,
                        'fetch_data' => true,
                    ]
                ),
            ],
            provider: [self::class, 'provideForOrganization'],
        ),
    ]
)]
class Staff
{
    public function __construct(public readonly int $id, public readonly string $name, public readonly ?Organization $organization = null)
    {
    }

    public static function provideForOrganization(Operation $operation, array $uriVariables = [], array $context = []): iterable
    {
        $organization = $operation->getParameters()->get('organization', HeaderParameter::class)->getValue()[0];

        assert($organization instanceof Organization);

        return [
            new self(1, 'John Doe', $organization),
            new self(2, 'Jane Smith', $organization),
        ];
    }
}

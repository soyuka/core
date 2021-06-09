<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
class AbsoluteUrlDummy
{
}

?>
-----
<?php

namespace ApiPlatform\Tests\Bridge\Rector\Rules\LegacyApiResourceAttributeToApiResourceAttributeRector\Fixture;

use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Core\Api\UrlGeneratorInterface;

#[ApiResource(urlGenerationStrategy: UrlGeneratorInterface::ABS_URL)]
class AbsoluteUrlDummy
{
}

?>
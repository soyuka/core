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

namespace ApiPlatform\Core\Tests\Behat;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\ApiTestCase;
use Behat\Gherkin\Node\PyStringNode;
use Behatch\Context\JsonContext as BaseJsonContext;
use Behatch\HttpCall\HttpCallResultPool;
use Behatch\Json\Json;
use PHPUnit\Framework\Assert;

final class JsonContext extends BaseJsonContext
{
    public function __construct(HttpCallResultPool $httpCallResultPool)
    {
        parent::__construct($httpCallResultPool);
    }

    public function theJsonShouldBeEqualTo(PyStringNode $content): void
    {
        $actual = $this->getJson();

        try {
            $expected = new Json($content);
        } catch (\Exception $e) {
            throw new \Exception('The expected JSON is not a valid');
        }

        $this->assertEquals(
            $expected->getContent(),
            $actual->getContent(),
            "The json is equal to:\n{$actual->encode()}"
        );
    }

    /**
     * @Then /^the JSON should be a superset of:$/
     */
    public function theJsonIsASupersetOf(PyStringNode $content)
    {
        $array = json_decode($this->httpCallResultPool->getResult()->getValue(), true);
        $subset = json_decode($content->getRaw(), true);

        method_exists(Assert::class, 'assertArraySubset') ? Assert::assertArraySubset($subset, $array) : ApiTestCase::assertArraySubset($subset, $array); // @phpstan-ignore-line Compatibility with PHPUnit 7
    }
}

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

namespace Problem\Serializer;

use ApiPlatform\Api\IriConverterInterface;
use ApiPlatform\ApiResource\ProblemError;
use ApiPlatform\Problem\Serializer\ErrorExceptionNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

class ErrorExceptionNormalizerTest extends TestCase
{
    use ProphecyTrait;

    public function testSupportsNormalization(): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);

        $normalizer = new ErrorExceptionNormalizer($iriConverterProphecy->reveal());

        $this->assertTrue($normalizer->supportsNormalization(new \Exception(), ErrorExceptionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new \Exception(), 'xml'));
        $this->assertFalse($normalizer->supportsNormalization(new \stdClass(), ErrorExceptionNormalizer::FORMAT));
        $this->assertFalse($normalizer->supportsNormalization(new FlattenException(), ErrorExceptionNormalizer::FORMAT));
        $this->assertTrue($normalizer->hasCacheableSupportsMethod());
    }

    /**
     * @dataProvider providerStatusCode
     */
    public function testNormalize(int $statusCode, string $message, bool $debug): void
    {
        $iriConverterProphecy = $this->prophesize(IriConverterInterface::class);
        $normalizerProphecy = $this->prophesize(NormalizerInterface::class);

        $error = ProblemError::createFromException(new \Exception($message), $statusCode);

        $iriConverterProphecy->getIriFromResource($error)->willReturn('/problems/'.$statusCode)->shouldBeCalled();
        $normalizerProphecy
            ->normalize($error, format: null, context: [AbstractNormalizer::IGNORED_ATTRIBUTES => ['trace', 'traceAsString', 'message', 'file', 'line', 'code', 'previous', 'identifiers']])
            ->willReturn(['title' => $message, 'detail' => $message, 'status' => $statusCode, 'instance' => ''])
            ->shouldBeCalled();

        $normalizer = new ErrorExceptionNormalizer($iriConverterProphecy->reveal(), $debug);
        $normalizer->setNormalizer($normalizerProphecy->reveal());

        $expected = [
            '@type' => 'hydra:Error',
            'title' => $message,
            'detail' => $message,
            'status' => $statusCode,
            'instance' => '',
            'type' => '/problems/'.$statusCode,
        ];
        if ($debug) {
            $expected['trace'] = $error->getTrace();
        }

        $this->assertEquals(
            $expected,
            $normalizer->normalize($error)
        );
    }

    public function providerStatusCode(): \Iterator
    {
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', false];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', false];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', false];
        yield [Response::HTTP_INTERNAL_SERVER_ERROR, 'Sensitive SQL error displayed', true];
        yield [Response::HTTP_GATEWAY_TIMEOUT, 'Sensitive server error displayed', true];
        yield [Response::HTTP_BAD_REQUEST, 'Bad Request Message', true];
    }
}

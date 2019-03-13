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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Test;

use ApiPlatform\Core\Bridge\Symfony\Bundle\Test\Exception\ClientException;
use Symfony\Component\BrowserKit\Response as BrowserKitResponse;
use Symfony\Component\HttpClient\Exception\JsonException;
use Symfony\Component\HttpFoundation\Response as HttpFoundationResponse;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;

/**
 * HTTP Response.
 *
 * @internal
 *
 * Partially copied from \Symfony\Component\HttpClient\Response\ResponseTrait
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Response implements ResponseInterface
{
    private $httpFoundationResponse;
    private $browserKitResponse;
    private $info;
    private $content;
    private $jsonData;

    public function __construct(HttpFoundationResponse $httpFoundationResponse, BrowserKitResponse $browserKitResponse, array $info)
    {
        $this->httpFoundationResponse = $httpFoundationResponse;
        $this->browserKitResponse = $browserKitResponse;

        $this->headers = $httpFoundationResponse->headers->all();
        $this->content = $httpFoundationResponse->getContent();
        $this->info = $info + [
            'http_code' => $httpFoundationResponse->getStatusCode(),
            'error' => null,
            'raw_headers' => $this->headers,
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo(string $type = null)
    {
        if ($type) {
            return $this->info[$type] ?? null;
        }

        return $this->info;
    }

    /**
     * Checks the status, and try to extract message if appropriate.
     */
    private function checkStatusCode()
    {
        $message = 'An error '.$this->info['http_code'].' occured.';
        if (isset($this->headers['content-type'][0]) && false !== preg_match('#^application/(?:.+\+)?json#', $this->headers['content-type'][0])) {
            if ($json = json_decode($this->content, true)) {
                // Try to extract the error message from Hydra or RFC 7807 error structures
                $message = $json['hydra:description'] ?? $json['hydra:title'] ?? $json['detail'] ?? $json['title'] ?? $message;
            }
        }

        if (500 <= $this->info['http_code']) {
            throw new ServerException($message, $this->info['http_code']);
        }

        if (400 <= $this->info['http_code']) {
            throw new ClientException($message, $this->info['http_code']);
        }

        if (300 <= $this->info['http_code']) {
            throw new RedirectionException($message, $this->info['http_code']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getContent(bool $throw = true): string
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->content;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode(): int
    {
        return $this->info['http_code'];
    }

    /**
     * {@inheritdoc}
     */
    public function getHeaders(bool $throw = true): array
    {
        if ($throw) {
            $this->checkStatusCode();
        }

        return $this->headers;
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(bool $throw = true): array
    {
        if (null !== $this->jsonData) {
            return $this->jsonData;
        }

        if ('' === $content = $this->getContent($throw)) {
            throw new class(sprintf('Response body is empty.', $contentType)) extends \Exception implements TransportExceptionInterface {
            };
        }

        $contentType = $this->headers['content-type'][0] ?? 'application/json';

        if (!preg_match('/\bjson\b/i', $contentType)) {
            throw new class(sprintf('Response content-type is "%s" while a JSON-compatible one was expected.', $contentType)) extends \JsonException implements TransportExceptionInterface {
            };
        }

        try {
            $content = json_decode($content, true, 512, JSON_BIGINT_AS_STRING | (\PHP_VERSION_ID >= 70300 ? JSON_THROW_ON_ERROR : 0));
        } catch (\JsonException $e) {
            throw new class($e->getMessage(), $e->getCode()) extends \JsonException implements TransportExceptionInterface {
            };
        }

        if (\PHP_VERSION_ID < 70300 && JSON_ERROR_NONE !== json_last_error()) {
            throw new JsonException(json_last_error_msg(), json_last_error());
        }

        if (!\is_array($content)) {
            throw new class(sprintf('JSON content was expected to decode to an array, %s returned.', \gettype($content))) extends \JsonException implements TransportExceptionInterface {
            };
        }

        return $this->jsonData = $content;
    }

    /**
     * Returns the internal HttpKernel response.
     */
    public function getKernelResponse(): HttpFoundationResponse
    {
        return $this->httpFoundationResponse;
    }

    /**
     * Returns the internal BrowserKit reponse.
     */
    public function getBrowserKitResponse(): BrowserKitResponse
    {
        return $this->browserKitResponse;
    }
}

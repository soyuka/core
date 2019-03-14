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

use Symfony\Bundle\FrameworkBundle\Client as FrameworkBundleClient;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpClient\HttpClientTrait;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\HttpKernel\Profiler\Profile as HttpProfile;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Convenient test client that makes requests to a Kernel object.
 *
 * @experimental
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 */
final class Client implements HttpClientInterface
{
    public const OPTIONS_DEFAULT = [
        'auth_basic' => null,                               // array|string - an array containing the username as first value, and optionally the
                                                            //   password as the second one; or string like username:password - enabling HTTP Basic
                                                            //   authentication (RFC 7617)
        'auth_bearer' => null,                              // string - a token enabling HTTP Bearer authorization (RFC 6750)
        'query' => [],                                      // string[] - associative array of query string values to merge with the request's URL
        'headers' => ['accept' => ['application/ld+json']], // iterable|string[]|string[][] - headers names provided as keys or as part of values
        'body' => '',                                       // array|string|resource|\Traversable|\Closure - the callback SHOULD yield a string
        'json' => null,                                     // array|\JsonSerializable - when set, implementations MUST set the "body" option to
        //   the JSON-encoded value and set the "content-type" headers to a JSON-compatible
        'base_uri' => 'http://example.com',                 // string - the URI to resolve relative URLs, following rules in RFC 3986, section 2
    ];

    use HttpClientTrait;

    private $fwbClient;

    public function __construct(FrameworkBundleClient $fwbClient)
    {
        $this->fwbClient = $fwbClient;
        $fwbClient->followRedirects(false);
    }

    /**
     * @see Client::OPTIONS_DEFAULTS for available options
     *
     * {@inheritdoc}
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if (isset($options['body'])) {
            if (isset($options['headers'])) {
                $options['headers'] = self::normalizeHeaders($options['headers']);
            }

            $json = false;
            if (!isset($options['headers']['content-type'][0])) {
                // Content-Type default to JSON-LD if a body is set, but no Content-Type is defined
                $options['headers']['content-type'] = $options['headers']['content-type'] ?? ['application/ld+json'];
                $json = true;
            }

            if (
                (\is_array($options['body']) || $options['body'] instanceof \JsonSerializable) &&
                (
                    $json ||
                    preg_match('/\bjson\b/i', $options['headers']['content-type'][0])
                )
            ) {
                // Encode the JSON
                $options['json'] = $options['body'];
            }
        }

        $basic = $options['auth_basic'] ?? null;
        [$url, $options] = $this->prepareRequest($method, $url, $options, self::OPTIONS_DEFAULT);

        $server = [];
        // Convert headers to a $_SERVER-like array
        foreach ($options['headers'] as $key => $value) {
            if ('content-type' === $key) {
                $server['CONTENT_TYPE'] = $value[0] ?? '';

                continue;
            }

            // BrowserKit doesn't support setting several headers with the same name
            $server['HTTP_'.strtoupper(str_replace('-', '_', $key))] = $value[0] ?? '';
        }

        if ($basic) {
            $credentials = is_array($basic) ? $basic : explode(':', $basic, 2);
            $server['PHP_AUTH_USER'] = $credentials[0];
            $server['PHP_AUTH_PW'] = $credentials[1] ?? '';
        }

        $info = [
            'redirect_count' => 0,
            'redirect_url' => null,
            'http_method' => $method,
            'start_time' => microtime(true),
            'data' => $options['data'] ?? null,
            'url' => $url,
        ];
        $this->fwbClient->request($method, implode('', $url), [], [], $server, $options['body'] ?? null);

        return new Response($this->fwbClient->getResponse(), $this->fwbClient->getInternalResponse(), $info);
    }

    /**
     * {@inheritdoc}
     */
    public function stream($responses, float $timeout = null): ResponseStreamInterface
    {
        throw new \LogicException('Not implemented yet');
    }

    /**
     * Gets the underlying test client.
     */
    public function getFrameworkBundleClient(): FrameworkBundleClient
    {
        return $this->fwbClient;
    }

    // The following methods are proxy methods for FrameworkBundleClient's ones

    /**
     * Returns the container.
     *
     * @return ContainerInterface|null Returns null when the Kernel has been shutdown or not started yet
     */
    public function getContainer()
    {
        return $this->fwbClient->getContainer();
    }

    /**
     * Returns the kernel.
     *
     * @return KernelInterface
     */
    public function getKernel()
    {
        return $this->fwbClient->getKernel();
    }

    /**
     * Gets the profile associated with the current Response.
     *
     * @return HttpProfile|false A Profile instance
     */
    public function getProfile()
    {
        return $this->fwbClient->getProfile();
    }

    /**
     * Enables the profiler for the very next request.
     *
     * If the profiler is not enabled, the call to this method does nothing.
     */
    public function enableProfiler()
    {
        $this->fwbClient->enableProfiler();
    }

    /**
     * Disables kernel reboot between requests.
     *
     * By default, the Client reboots the Kernel for each request. This method
     * allows to keep the same kernel across requests.
     */
    public function disableReboot()
    {
        $this->fwbClient->disableReboot();
    }

    /**
     * Enables kernel reboot between requests.
     */
    public function enableReboot()
    {
        $this->fwbClient->enableReboot();
    }
}

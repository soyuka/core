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

namespace ApiPlatform\Metadata\Resource;

use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;

/**
 * @internal
 */
trait SanitizeMetadataTrait
{
    private $camelCaseToSnakeCaseNameConverter;

    public function getKeyValue(string $key, $value)
    {
        if (!$this->camelCaseToSnakeCaseNameConverter) {
            $this->camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();
        }

        // Transform default value to an empty array if null
        if (\in_array($key, ['denormalization_context', 'normalization_context', 'hydra_context', 'openapi_context', 'order', 'pagination_via_cursor', 'exception_to_status'], true)) {
            if (null === $value) {
                $value = [];
            } elseif (!\is_array($value)) {
                $value = [$value];
            }
        } elseif ('route_prefix' === $key) {
            $value = \is_string($value) ? $value : '';
        } elseif ('query_parameter_validation_enabled' === $key) {
            $value = !$value ? false : $value;
        // GraphQl related keys
        } elseif ('filters' === $key) {
            $value = null === $value ? [] : $value;
        } elseif ('identifiers' === $key) {
            $key = 'uriVariables';
        } elseif ('doctrine_mongodb' === $key) {
            $key = 'extra_properties';
            $value = ['doctrine_mongodb' => $value];
        }

        return [$this->camelCaseToSnakeCaseNameConverter->denormalize($key), $value];
    }
}

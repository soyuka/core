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

namespace ApiPlatform\Core\Tests;

class DeprecationErrorHandler
{
    private $decorated;

    public static function register($mode = 0)
    {
        $handler = new self();
        $oldErrorHandler = set_error_handler([$handler, 'handleError']);
        $handler->setDecorated($oldErrorHandler);
    }

    /**
     * @internal
     */
    public function handleError($type, $msg, $file, $line, $context = [])
    {
        if ('Since api-platform/core 2.7: Use "ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface" instead of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface".' !== $msg) {
            \call_user_func($this->decorated, $type, $msg, $file, $line, $context);

            return;
        }
        // foreach (debug_backtrace() as $frame) {
        //     dump($frame['file']);
        // }
    }

    public function setDecorated($errorHandler)
    {
        $this->decorated = $errorHandler;
    }
}

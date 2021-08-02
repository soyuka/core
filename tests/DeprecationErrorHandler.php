<?php

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
        if ($msg !== 'Since api-platform/core 2.7: Use "ApiPlatform\Metadata\Resource\Factory\ResourceMetadataCollectionFactoryInterface" instead of "ApiPlatform\Core\Metadata\Resource\Factory\ResourceMetadataFactoryInterface".') {
            call_user_func($this->decorated, $type, $msg, $file, $line, $context);
            return;
        }
        // foreach (debug_backtrace() as $frame) {
        //     dump($frame['file']);
        // }
    }

    public function setDecorated($errorHandler) {
        $this->decorated = $errorHandler;
    }
}

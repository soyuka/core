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

namespace ApiPlatform\State\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\Metadata\Util\ClassInfoTrait;
use ApiPlatform\State\ProcessorInterface;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bridges persistence and the API system.
 *
 * @author Kévin Dunglas <dunglas@gmail.com>
 * @author Baptiste Meyer <baptiste.meyer@gmail.com>
 */
final class WriteProcessor implements ProcessorInterface
{
    use ClassInfoTrait;

    public function __construct(private readonly ProcessorInterface $processor, private readonly ProcessorInterface $callableProcessor)
    {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = [])
    {
        if (
            $data instanceof Response
            || !($operation->canWrite() ?? true)
            || !$operation->getProcessor()
            // || $request->isMethodSafe()
            // || !($attributes = RequestAttributesExtractor::extractAttributes($request))
        ) {
            return $this->processor->process($data, $operation, $uriVariables, $context);
        }

        return $this->processor->process($this->callableProcessor->process($data, $operation, $uriVariables, $context), $operation, $uriVariables, $context);

        // if ($this->resourceClassResolver->isResourceClass($this->getObjectClass($data))) {
        //     $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromResource($data));
        // }
        // if ($persistResult) {
        //     $controllerResult = $persistResult;
        //     $event->setControllerResult($controllerResult);
        // }
        //
        // if ($controllerResult instanceof Response) {
        //     break;
        // }
        //
        // $outputMetadata = $operation->getOutput() ?? ['class' => $attributes['resource_class']];
        // $hasOutput = \is_array($outputMetadata) && \array_key_exists('class', $outputMetadata) && null !== $outputMetadata['class'];
        // if (!$hasOutput) {
        //     break;
        // }
        //
        // if ($this->resourceClassResolver->isResourceClass($this->getObjectClass($controllerResult))) {
        //     $request->attributes->set('_api_write_item_iri', $this->iriConverter->getIriFromResource($controllerResult));
        // }

                // break;
            // case 'DELETE':
            //     $this->processor->process($controllerResult, $operation, $uriVariables, $context);
            //     $event->setControllerResult(null);
            //     break;
    }
}

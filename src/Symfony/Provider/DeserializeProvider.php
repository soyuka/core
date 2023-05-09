<?php

namespace ApiPlatform\Symfony\Provider;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use Symfony\Component\Serializer\Exception\PartialDenormalizationException;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;
use ApiPlatform\Serializer\OperationAwareSerializerContextBuilderInterface;
use ApiPlatform\Api\FormatMatcher;
use ApiPlatform\Metadata\HttpOperation;
use ApiPlatform\Symfony\Validator\Exception\ValidationException;
use Symfony\Component\HttpKernel\Exception\UnsupportedMediaTypeHttpException;
use Symfony\Component\Serializer\Exception\NotNormalizableValueException;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Constraints\Type;
use Symfony\Component\Validator\ConstraintViolation;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Contracts\Translation\TranslatorTrait;

final class DeserializeProvider implements ProviderInterface
{
    public function __construct(private readonly ProviderInterface $inner, private readonly SerializerInterface $serializer, private readonly OperationAwareSerializerContextBuilderInterface $serializerContextBuilder, private ?TranslatorInterface $translator = null)
    {
        if (null === $this->translator) {
            $this->translator = new class() implements TranslatorInterface, LocaleAwareInterface {
                use TranslatorTrait;
            };
            $this->translator->setLocale('en');
        }
    }

    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $data = $this->inner->provide($operation, $uriVariables, $context);
        $method = $operation->getMethod();

        if (
            !($operation->canDeserialize() ?? true)
            || !in_array($method, ['POST', 'PUT', 'PATCH'], true)
        ) {
            return $data;
        }

        // TODO: this interface need a change and use Operation instead
        /// $serializerContext = $this->serializerContextBuilder->createFromRequest($request, false, $attributes);
        $serializerContext = $this->serializerContextBuilder->createFromOperation($operation, normalization: false);
        if (!$format = $context['input_format'] ?? null) {
            throw new UnsupportedMediaTypeHttpException('Format not supported.');
        }

        if (
            null !== $data
            && (
                'POST' === $method
                || 'PATCH' === $method
                || ('PUT' === $method && !($operation->getExtraProperties()['standard_put'] ?? false))
            )
        ) {
            $context[AbstractNormalizer::OBJECT_TO_POPULATE] = $data;
        }

        try {
            return $this->serializer->deserialize($context['request_content'], $operation->getClass(), $format, $serializerContext);
        } catch (PartialDenormalizationException $e) {
            $violations = new ConstraintViolationList();
            foreach ($e->getErrors() as $exception) {
                if (!$exception instanceof NotNormalizableValueException) {
                    continue;
                }
                $message = (new Type($exception->getExpectedTypes() ?? []))->message;
                $parameters = [];
                if ($exception->canUseMessageForUser()) {
                    $parameters['hint'] = $exception->getMessage();
                }
                $violations->add(new ConstraintViolation($this->translator->trans($message, ['{{ type }}' => implode('|', $exception->getExpectedTypes() ?? [])], 'validators'), $message, $parameters, null, $exception->getPath(), null, null, (string) $exception->getCode()));
            }
            if (0 !== \count($violations)) {
                throw new ValidationException($violations);
            }
        }
    }
}


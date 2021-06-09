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

namespace ApiPlatform\Core\Bridge\Rector\Rules;

use ApiPlatform\Metadata\Resource\DeprecationMetadataTrait;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagValueNode;
use Rector\BetterPhpDocParser\PhpDoc\DoctrineAnnotationTagValueNode;
use Rector\BetterPhpDocParser\PhpDocInfo\PhpDocInfo;
use Rector\BetterPhpDocParser\PhpDocManipulator\PhpDocTagRemover;
use Rector\Core\Contract\Rector\ConfigurableRectorInterface;
use Rector\Core\ValueObject\PhpVersionFeature;
use Rector\Php80\ValueObject\AnnotationToAttribute;
use Rector\PhpAttribute\Printer\PhpAttributeGroupFactory;
use Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter;
use Symplify\RuleDocGenerator\ValueObject\CodeSample\ConfiguredCodeSample;
use Symplify\RuleDocGenerator\ValueObject\RuleDefinition;
use Webmozart\Assert\Assert;

/**
 * @experimental
 */
final class ApiResourceAnnotationToApiResourceAttributeRector extends AbstractLegacyApiResourceToApiResourceAttribute implements ConfigurableRectorInterface
{
    use DeprecationMetadataTrait;

    /**
     * @var string
     */
    public const ANNOTATION_TO_ATTRIBUTE = 'api_resource_annotation_to_api_resource_attribute';
    /**
     * @var string
     */
    public const REMOVE_TAG = 'remove_tag';
    /**
     * @var AnnotationToAttribute[]
     */
    private $annotationsToAttributes = [];
    /**
     * @var bool
     */
    private $removeTag;
    /**
     * @var PhpDocTagRemover
     */
    private $phpDocTagRemover;

    public function __construct(PhpAttributeGroupFactory $phpAttributeGroupFactory, PhpDocTagRemover $phpDocTagRemover)
    {
        $this->phpAttributeGroupFactory = $phpAttributeGroupFactory;
        $this->phpDocTagRemover = $phpDocTagRemover;
    }

    public function getRuleDefinition(): RuleDefinition
    {
        return new RuleDefinition('Change annotation to attribute', [new ConfiguredCodeSample(<<<'CODE_SAMPLE'
use ApiPlatform\Core\Annotation\ApiResource;

/**
 * @ApiResource(collectionOperations={}, itemOperations={
 *     "get",
 *     "get_by_isbn"={"method"="GET", "path"="/books/by_isbn/{isbn}.{_format}", "requirements"={"isbn"=".+"}, "identifiers"="isbn"}
 * })
 */
class Book
CODE_SAMPLE
            , <<<'CODE_SAMPLE'
use ApiPlatform\Metadata\ApiResource;
use ApiPlatform\Metadata\Get;

#[ApiResource]
#[Get]
#[Get(operationName: 'get_by_isbn', path: '/books/by_isbn/{isbn}.{_format}', requirements: ['isbn' => '.+'], identifiers: 'isbn')]
class Book
CODE_SAMPLE
            , [
                self::ANNOTATION_TO_ATTRIBUTE => [new AnnotationToAttribute(\ApiPlatform\Core\Annotation\ApiResource::class, \ApiPlatform\Core\Annotation\ApiResource::class)],
                self::REMOVE_TAG => true,
            ]),
        ]);
    }

    /**
     * @return array<class-string<Node>>
     */
    public function getNodeTypes(): array
    {
        return [Class_::class];
    }

    /**
     * @param Class_ $node
     */
    public function refactor(Node $node): ?Node
    {
        if (!$this->phpVersionProvider->isAtLeastPhpVersion(PhpVersionFeature::ATTRIBUTES)) {
            return null;
        }
        $phpDocInfo = $this->phpDocInfoFactory->createFromNode($node);
        if (!$phpDocInfo instanceof PhpDocInfo) {
            return null;
        }
        $tags = $phpDocInfo->getPhpDocNode()->getTags();
        $hasNewAttrGroups = $this->processApplyAttrGroups($tags, $phpDocInfo, $node);
        if ($hasNewAttrGroups) {
            return $node;
        }

        return null;
    }

    /**
     * @param array<string, AnnotationToAttribute[]> $configuration
     */
    public function configure(array $configuration): void
    {
        $annotationsToAttributes = $configuration[self::ANNOTATION_TO_ATTRIBUTE] ?? [];
        Assert::allIsInstanceOf($annotationsToAttributes, AnnotationToAttribute::class);
        $this->annotationsToAttributes = $annotationsToAttributes;
        $this->removeTag = $configuration[self::REMOVE_TAG] ?? true;
    }

    /**
     * @param array<PhpDocTagNode> $tags
     * @param Class_               $node
     */
    private function processApplyAttrGroups(array $tags, PhpDocInfo $phpDocInfo, Node $node): bool
    {
        $hasNewAttrGroups = false;
        foreach ($tags as $tag) {
            foreach ($this->annotationsToAttributes as $annotationToAttribute) {
                $annotationToAttributeTag = $annotationToAttribute->getTag();
                if ($phpDocInfo->hasByName($annotationToAttributeTag)) {
                    if (true === $this->removeTag) {
                        // 1. remove php-doc tag
                        $this->phpDocTagRemover->removeByName($phpDocInfo, $annotationToAttributeTag);
                    }
                    // 2. add attributes
                    $node->attrGroups[] = $this->phpAttributeGroupFactory->createFromSimpleTag($annotationToAttribute);
                    $hasNewAttrGroups = true;
                    continue 2;
                }
                if ($this->shouldSkip($tag->value, $phpDocInfo, $annotationToAttributeTag)) {
                    continue;
                }

                if (true === $this->removeTag) {
                    // 1. remove php-doc tag
                    $this->phpDocTagRemover->removeTagValueFromNode($phpDocInfo, $tag->value);
                }
                // 2. add attributes
                /** @var DoctrineAnnotationTagValueNode $tagValue */
                $tagValue = clone $tag->value;
                $this->resolveOperations($tagValue, $node);

                // TODO: refactor this instead of multiple if
                if ($graphQlValue = $tagValue->getValue('graphql')) {
                    $tagValue->values['graphQlOperations'] = $graphQlValue;
                    unset($tagValue->values['graphql']);
                }

                $camelCaseToSnakeCaseNameConverter = new CamelCaseToSnakeCaseNameConverter();

                foreach ($tagValue->getValue('attributes')->values ?? [] as $attribute => $value) {
                    $tagValue->values[$camelCaseToSnakeCaseNameConverter->denormalize($attribute)] = $value;
                }

                $tagValue->removeValue('attributes');

                foreach ($tagValue->values ?? [] as $attribute => $value) {
                    [$updatedAttribute, $updatedValue] = $this->getKeyValue(str_replace('"', '', $camelCaseToSnakeCaseNameConverter->normalize($attribute)), $value);
                    if ($attribute !== $updatedAttribute) {
                        $tagValue->values[$updatedAttribute] = $updatedValue;
                        unset($tagValue->values[$attribute]);
                    }
                }

                $resourceAttributeGroup = $this->phpAttributeGroupFactory->create($tagValue, $annotationToAttribute);
                array_unshift($node->attrGroups, $resourceAttributeGroup);
                $hasNewAttrGroups = true;
                continue 2;
            }
        }

        return $hasNewAttrGroups;
    }

    private function shouldSkip(PhpDocTagValueNode $phpDocTagValueNode, PhpDocInfo $phpDocInfo, string $annotationToAttributeTag): bool
    {
        $doctrineAnnotationTagValueNode = $phpDocInfo->getByAnnotationClass($annotationToAttributeTag);
        if ($phpDocTagValueNode !== $doctrineAnnotationTagValueNode) {
            return true;
        }

        return !$phpDocTagValueNode instanceof DoctrineAnnotationTagValueNode;
    }

    /**
     * @param Class_ $node
     */
    private function resolveOperations(DoctrineAnnotationTagValueNode $tagValue, Node $node): void
    {
        $values = $tagValue->getValues();

        foreach ($this->operationTypes as $type) {
            if (isset($values[$type])) {
                $operations = $this->normalizeOperations($values[$type]->getValuesWithExplicitSilentAndWithoutQuotes());
                foreach ($operations as $name => $arguments) {
                    $node->attrGroups[] = $this->createOperationAttributeGroup($type, $name, $arguments);
                }
                // Remove collectionOperations|itemOperations from Tag values
                $tagValue->removeValue($type);
            }
        }
    }
}
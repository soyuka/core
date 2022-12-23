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

require '../vendor/autoload.php';

// This script generates a PHP reference from the given namespaces
// It's configuration can be found in `pdg.config.js`
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

$parser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
$lexer = new Lexer();
$finder = new Finder();

function isAttribute(ReflectionClass $reflectionClass): bool
{
    foreach ($reflectionClass->getAttributes() as $attribute) {
        if ('Attribute' === $attribute->getName()) {
            return true;
        }
    }

    return false;
}

function isImmutable(ReflectionProperty $reflectionProperty, ReflectionClass $reflectionClass): bool
{
    return $reflectionClass->hasMethod('get'.ucfirst($reflectionProperty->getName())) && $reflectionClass->hasMethod('with'.ucfirst($reflectionProperty->getName()));
}

function getPhpDoc(ReflectionMethod|ReflectionProperty|ReflectionClassConstant $reflection, PhpDocParser $parser, Lexer $lexer): PhpDocNode
{
    if (!($docComment = $reflection->getDocComment())) {
        return new PhpDocNode([]);
    }

    $tokens = new TokenIterator($lexer->tokenize($docComment));
    $v = $parser->parse($tokens);
    $tokens->consumeTokenType(Lexer::TOKEN_END);

    return $v;
}

function getModifier(ReflectionMethod|ReflectionProperty $reflection): string
{
    return implode(' ', \Reflection::getModifierNames($reflection->getModifiers()));
}

// Phpstan format has parenthesis and spaces
function formatType(string $type)
{
    if (0 === strpos($type, '(')) {
        $type = substr(substr($type, 1), 0, strlen($type) - 2);
    }

    return sprintf('`%s`', str_replace(' ', '', $type));
}

/**
 * @param ParamTagValueNode[] $constructorDocumentation
 */
function getTypeString(ReflectionProperty $reflectionProperty, PhpDocParser $parser, Lexer $lexer, array $constructorDocumentation): string
{
    $type = $reflectionProperty->getType();
    if ($type) {
        if ($type instanceof ReflectionUnionType) {
            $namedTypes = array_map(static function (ReflectionNamedType $namedType) {
                return linkClasses($namedType);
            }, $type->getTypes());
            return implode('|', $namedTypes);
        }
        if ($type instanceof ReflectionIntersectionType) {
            $namedTypes = array_map(static function (ReflectionNamedType $namedType) {
                return linkClasses($namedType);
            }, $type->getTypes());
            return implode('&', $namedTypes);
        }
        if ($type instanceof ReflectionNamedType) {
            return linkClasses($type);
        }

        return sprintf('`%s`', $type);
    }

    // Read the php doc
    $propertyTypes = getPhpDoc($reflectionProperty, $parser, $lexer);
    if ($varTagValues = $propertyTypes->getVarTagValues()) {
        $type = $varTagValues[0]->type;

        return formatType((string) $type);
    }

    if (isset($constructorDocumentation[$reflectionProperty->getName()])) {
        return formatType((string) $constructorDocumentation[$reflectionProperty->getName()]->type);
    }

    return '';
}

function isAccessor(ReflectionMethod $method): bool
{
    foreach ($method->getDeclaringClass()->getProperties() as $property) {
        if (str_contains($method->getName(), ucfirst($property->getName()))) {
            return true;
        }
    }
    return false;
}

/**
 * @param ReflectionMethod $method
 * @return array<string, string>
 */
function getParametersWithType(ReflectionMethod $method): array
{
    $typedParameters = [];
    foreach ($method->getParameters() as $parameter) {
        $parameterName = getParameterName($parameter);
        $type = $parameter->getType();
        if ($type) {
            if ($type instanceof ReflectionUnionType) {
                $namedTypes = array_map(static function (ReflectionNamedType $namedType) {
                    return linkClasses($namedType);
                }, $type->getTypes());

                $typedParameters[] = implode('|', $namedTypes)." ".addCssClasses($parameterName, ['token', 'variable']).getDefaultValueString($parameter);
            }
            if ($type instanceof ReflectionIntersectionType) {
                $namedTypes = array_map(static function (ReflectionNamedType $namedType) {
                    return linkClasses($namedType);
                }, $type->getTypes());

                $typedParameters[] = implode('&', $namedTypes)." ".addCssClasses($parameterName, ['token', 'variable']).getDefaultValueString($parameter);
            }
            if ($type instanceof ReflectionNamedType) {
                $typedParameters[] = linkClasses($type)." ".addCssClasses($parameterName, ['token', 'variable']).getDefaultValueString($parameter);
            }
        } else {
            $typedParameters[] = addCssClasses($parameterName, ['token', 'variable']).getDefaultValueString($parameter);
        }
    }
    return $typedParameters;
}

function getParameterName(ReflectionParameter $parameter): string
{
    return $parameter->isPassedByReference() ? '&$'.$parameter->getName() : '$'.$parameter->getName();
}

function getDefaultValueString(ReflectionParameter $parameter): string
{
    if (!$parameter->isDefaultValueAvailable()) {
        return '';
    }
    return match ($parameter->getDefaultValue()) {
        null => ' = null',
        [] => ' = []',
        default => ' = '.$parameter->getDefaultValue()
    };
}

function getReturnType(ReflectionMethod $method): string
{
    $type = $method->getReturnType();

    if ($type) {
        if ($type instanceof ReflectionUnionType) {
            return implode('|', array_map(static function(ReflectionNamedType $reflectionNamedType): string {
                return linkClasses($reflectionNamedType);
            }, $type->getTypes()
            ));
        }
        if ($type instanceof ReflectionIntersectionType) {
            return implode('&', array_map(static function(ReflectionNamedType $reflectionNamedType): string {
                return linkClasses($reflectionNamedType);
            }, $type->getTypes()
            ));
        }
        return linkClasses($type);
    } else {
        return "";
    }
}

/**
 * @param ReflectionType|ReflectionNamedType $reflectionNamedType
 * @return string
 */
function linkClasses(ReflectionType|ReflectionNamedType $reflectionNamedType): string
{
    if (class_exists($reflectionNamedType->getName()) || interface_exists($reflectionNamedType->getName())) {
        if (str_starts_with($reflectionNamedType->getName(), 'ApiPlatform')) {
            return "[$reflectionNamedType](/reference/" . str_replace(['ApiPlatform\\', '\\'], ['', '/'], $reflectionNamedType->getName()) . ')';
        } else if (str_starts_with($reflectionNamedType->getName(), 'Symfony')) {
            return "[$reflectionNamedType](https://symfony.com/doc/current/index.html)";
        }
    }
    return $reflectionNamedType->getName();
}

function addLink(ReflectionClass $class): string
{
    if (class_exists($class->getName()) || interface_exists($class->getName()) || trait_exists($class->getName())) {
        if (str_starts_with($class->getName(), 'ApiPlatform')) {
            return "[{$class->getName()}](/reference/" . str_replace(['ApiPlatform\\', '\\'], ['', '/'], $class->getName()) . ')';
        } else if (str_starts_with($class->getName(), 'Symfony')) {
            return "[{$class->getName()}](https://symfony.com/doc/current/index.html)";
        } else if (!$class->isUserDefined()) {
            return "[\\{$class->getName()}](https://php.net/class." . strtolower($class->getName()) . ")";
        }
    }
    return $class->getName();
}

function getAccessors(ReflectionProperty $property): array
{
    $propertyName = ucfirst($property->getName());
    $accessors = [];

    foreach ($property->getDeclaringClass()->getMethods() as $method) {
        switch ($method->getName()) {
            case 'get'.$propertyName:
            case 'set'.$propertyName:
            case 'is'.$propertyName:
                $accessors[] = $method->getName();
                break;
            default:
                continue 2;
        }
    }
    return $accessors;
}

function addCssClasses(string $element, array $classes): string
{
    return sprintf("<span className=\"%s\">%s</span>", implode(' ', $classes), $element);
}

function isConstruct(ReflectionMethod $method): bool
{
    return "__construct" === $method->getName();
}

/**
 * Checks if a method is actually from a Trait or an extended class
 */
function isFromExternalClass(ReflectionMethod $method, ReflectionClass $class): bool
{
    return $method->getFileName() !== $class->getFileName();
}

function hasToBeSkipped(ReflectionMethod $method, ReflectionClass $reflectionClass): bool
{
    return isFromExternalClass($method, $reflectionClass)
        || str_contains(getModifier($method), "private")
        || isAccessor($method)
        || isConstruct($method)
        ;
}

function containsInheritDoc(PhpDocTextNode $textNode): bool
{
    return str_contains($textNode->text, '{@inheritdoc}');
}

function getInheritedDoc(ReflectionMethod $method, PhpDocParser $parser, Lexer $lexer): string
{
    $content = '';
    try {
        $parent = $method->getPrototype();
        // if it is a core php method, no need to look for phpdoc
        if (!$parent->isUserDefined()) {
            return $content;
        }
        $parentDoc = getPhpDoc($parent, $parser, $lexer);
        return printTextNodes($parentDoc, printThrowTags($parentDoc, $content));

    } catch (ReflectionException) {
        return $content;
    }
}

function printThrowTags(PhpDocNode $phpDoc, string $content): string
{
    /** @var PhpDocTagNode[] $tags */
    $tags = array_filter($phpDoc->children, static function (PhpDocChildNode $childNode): bool {
        return $childNode instanceof PhpDocTagNode;
    });

    foreach ($tags as $tag) {
        if ($tag->value instanceof ThrowsTagValueNode) {
            $content .= "> " . addCssClasses("throws ", ['token', 'keyword']) . $tag->value->type->name . \PHP_EOL . "> " . \PHP_EOL;
        }
    }
    return $content;
}

function printTextNodes(PhpDocNode $phpDoc, string $content): string
{
    $text = array_filter($phpDoc->children, static function (PhpDocChildNode $child): bool {
        return $child instanceof PhpDocTextNode;
    });

    foreach ($text as $t) {
        $content .= $t.\PHP_EOL;
    }
    return $content;
}

$handle = fopen($argv[1], 'r');
if (!$handle) {
    fwrite(STDERR, sprintf('Error opening %s. %s', $argv[1], \PHP_EOL));
    exit(1);
}

$config = (require('config.php'))()['reference'];
$file = Path::makeAbsolute($argv[1], getcwd());
$root = Path::makeAbsolute($config['src'], getcwd());
$relative = Path::makeRelative($file, $root);
fwrite(\STDERR, sprintf('Generating reference for %s.%s', $relative, \PHP_EOL));
$namespace = 'ApiPlatform\\'.str_replace(['/', '.php'], ['\\', ''], $relative);
$content = "";

$reflectionClass = new ReflectionClass($namespace);

/** @var ParamTagValueNode[] $propertiesConstructorDocumentation */
$propertiesConstructorDocumentation = [];
/** @var PhpDocNode[] $methodsDocumentation */
$methodsDocumentation = [];

if ($reflectionClass->hasMethod('__construct')) {
    $constructorDocumentation = getPhpDoc($reflectionClass->getMethod('__construct'), $parser, $lexer);
    foreach ($constructorDocumentation->getParamTagValues() as $paramTagValueNode) {
        $propertiesConstructorDocumentation[substr($paramTagValueNode->parameterName, 1)] = $paramTagValueNode;
    }
}

$content .= "# \\{$reflectionClass->getName()}".\PHP_EOL;

if ($parent = $reflectionClass->getParentClass()) {
    $content .= "### Extends: " . \PHP_EOL;
    $content .= "> ".addLink($parent). \PHP_EOL;
}

if (!empty($reflectionClass->getInterfaces())) {
    $content .= "### Implements ". \PHP_EOL;

    foreach ($reflectionClass->getInterfaces() as $interface) {
        $content .= "> ".addLink($interface). \PHP_EOL. "> " . \PHP_EOL;
    }
}

if (!isAttribute($reflectionClass)) {
    // todo document construct method
}

$rawDocNode = $reflectionClass->getDocComment();

if ($rawDocNode) {
    $tokens = new TokenIterator($lexer->tokenize($rawDocNode));
    $phpDocNode = $parser->parse($tokens);
    $tokens->consumeTokenType(Lexer::TOKEN_END);
    $text = array_filter($phpDocNode->children, static function (PhpDocChildNode $child): bool {
        return $child instanceof PhpDocTextNode;
    });

    /** @var PhpDocTextNode $t */
    foreach ($text as $t) {
        // todo {@see ... } breaks generation, but we can probably reference it better
        if (str_contains($t->text, '@see')) {
            $t = str_replace(array('{@see', '}'), array('see', ''), $t->text);
        }
        $content .= $t.\PHP_EOL;
    }
}
$constants = [];
foreach ($reflectionClass->getReflectionConstants(ReflectionClassConstant::IS_PUBLIC) as $constant) {
        $constants[] = $constant;
}
if (!empty($constants)) {
    $content .= "## Constants: ".\PHP_EOL;
}


foreach ($constants as $constant) {

    $content .= "### ".addCssClasses($constant->getName(), ['token', 'keyword']).' = '.$constant->getValue().\PHP_EOL;
    $constantDoc = getPhpDoc($constant, $parser, $lexer);
    $constantText = array_filter($constantDoc->children, static function (PhpDocChildNode $constantDocNode): bool {
        return $constantDocNode instanceof PhpDocTextNode;
    });

    foreach ($constantText as $text) {
        $content .= $text.\PHP_EOL;
    }
}

if (!empty($reflectionClass->getProperties())) {
    $content .= "## Properties: ".\PHP_EOL;
}

foreach ($reflectionClass->getProperties() as $property) {
    $modifier = getModifier($property);
    $accessors = [];
    if ('private' === $modifier) {
        $accessors = getAccessors($property);
    }

    $type = getTypeString($property, $parser, $lexer, $propertiesConstructorDocumentation);
    $content .= "<a className=\"anchor\" href=\"#{$property->getName()}\" id=\"{$property->getName()}\">§</a>".\PHP_EOL;
    $content .= "### {$type} \${$property->getName()}".\PHP_EOL;
    if (!empty($accessors)) {
        $content .= "Accessors: ".implode(',', $accessors).\PHP_EOL;
    }
    if (($propertyConstructor = $properties[$property->getName()] ?? false) && $propertyConstructor->description) {
        $content .= $propertyConstructor->description.\PHP_EOL;
    }

    $doc = getPhpDoc($property, $parser, $lexer);
    $content = printTextNodes($doc, $content);
}

$methods = [];
foreach ($reflectionClass->getMethods() as $method) {
    if (!hasToBeSkipped($method, $reflectionClass)) {
        $methods[] = $method;
    }
}

if (!empty($methods)) {
    $content .= "## Methods: ".\PHP_EOL;
}


foreach ($methods as $method) {

    $typedParameters = getParametersWithType($method);

    $content .= "### "
        .getModifier($method)
        ." "
        .addCssClasses($method->getName(), ['token', 'function'])
        ."( "
        .implode(', ', $typedParameters)
        ." ): "
        .addCssClasses(getReturnType($method), ['token', 'keyword'])
        .\PHP_EOL;

    $phpDoc = getPhpDoc($method, $parser, $lexer);
    $text = array_filter($phpDoc->children, static function (PhpDocChildNode $child): bool {
        return $child instanceof PhpDocTextNode;
    });
    $content = printThrowTags($phpDoc, $content);

    /** @var PhpDocTextNode $t */
    foreach ($text as $t) {
        if (containsInheritDoc($t)) {
            // Imo Trait method should not have @inheritdoc as they might not "inherit" depending
            // on the using class
            if ($reflectionClass->isTrait()) {
                continue;
            }
            $t = getInheritedDoc($method, $parser, $lexer);
        }
        if (!empty((string)$t)) {
            $content .= $t.\PHP_EOL;
        }
    }

    $content .= \PHP_EOL."---".\PHP_EOL;
}

    fwrite(\STDOUT, $content);

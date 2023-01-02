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

namespace PDG\Services\Reference;

use PhpParser\Node;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\ThrowsTagValueNode;

class OutputFormatter
{
    public function addCssClasses(string $element, array $classes): string
    {
        return sprintf('<span className="%s">%s</span>', implode(' ', $classes), $element);
    }

    public function addLink(\ReflectionClass $class): string
    {
        if (!class_exists($name = $class->getName()) && !interface_exists($name) && !trait_exists($name)) {
            return $name;
        }
        if (str_starts_with($name, 'ApiPlatform')) {
            return "[{$name}](/reference/".str_replace(['ApiPlatform\\', '\\'], ['', '/'], $name).')';
        }
        if (str_starts_with($name, 'Symfony')) {
            return "[{$name}](https://symfony.com/doc/current/index.html)";
        }
        if (!$class->isUserDefined()) {
            return "[\\{$name}](https://php.net/class.".strtolower($name).')';
        }

        return $name;
    }

    public function linkClasses(\ReflectionType|\ReflectionNamedType $reflectionNamedType): string
    {
        if (!class_exists($name = $reflectionNamedType->getName()) && !interface_exists($name)) {
            if ($reflectionNamedType instanceof \ReflectionNamedType && $reflectionNamedType->allowsNull()) {
                return '?'.$name;
            }

            return $name;
        }
        if (str_starts_with($name, 'ApiPlatform')) {
            return "[$reflectionNamedType](/reference/".str_replace(['ApiPlatform\\', '\\'], ['', '/'], $name).')';
        }
        if (str_starts_with($name, 'Symfony')) {
            return "[$reflectionNamedType](https://symfony.com/doc/current/index.html)";
        }

        return $name;
    }

    public function printTextNodes(PhpDocNode $phpDoc, string $content): string
    {
        $text = array_filter($phpDoc->children, static function (PhpDocChildNode $child): bool {
            return $child instanceof PhpDocTextNode;
        });

        foreach ($text as $t) {
            $content .= $t.\PHP_EOL;
        }

        return $content;
    }

    public function printThrowTags(PhpDocNode $phpDoc, string $content): string
    {
        /** @var PhpDocTagNode[] $tags */
        $tags = array_filter($phpDoc->children, static function (PhpDocChildNode $childNode): bool {
            return $childNode instanceof PhpDocTagNode;
        });

        foreach ($tags as $tag) {
            if ($tag->value instanceof ThrowsTagValueNode) {
                $content .= '> '.$this->addCssClasses('throws ', ['token', 'keyword']).$tag->value->type->name.\PHP_EOL.'> '.\PHP_EOL;
            }
        }

        return $content;
    }

    public function formatType(string $type): string
    {
        if (str_starts_with($type, '(')) {
            $type = substr(substr($type, 1), 0, \strlen($type) - 2);
        }

        return sprintf('`%s`', str_replace(' ', '', $type));
    }

    public function arrayNodeToString(Node\Expr\Array_ $array): string
    {
        if (!$items = $array->items) {
            return '[]';
        }
        $return = '[';
        /** @var Node\Expr\ArrayItem $item */
        foreach ($items as $item) {
            // TODO: maybe also handle multi dimensional arrays
            if ($item->value instanceof Node\Scalar) {
                $return .= $item->value->getAttribute('rawValue').', ';
            }
            if ($item->value instanceof Node\Expr\ConstFetch) {
                $return .= $item->value->name->parts[0].', ';
            }
        }
        $return = substr($return, 0, -2);
        $return .= ']';

        return $return;
    }

    public function writePageTitle(\ReflectionClass $reflectionClass, string $content): string
    {
        $content .= 'import Head from "next/head";'.\PHP_EOL.\PHP_EOL;
        $content .= '<Head><title>'.$reflectionClass->getShortName().'</title></Head> '.\PHP_EOL.\PHP_EOL;

        return $content;
    }

    public function writeClassName(\ReflectionClass $reflectionClass, string $content): string
    {
        return $content."# \\{$reflectionClass->getName()}".\PHP_EOL;
    }
}

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

use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTagNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;


$config = (require('config.php'))();
$root = Path::makeAbsolute($config['reference']['src'], getcwd());
$patterns = $config['reference']['patterns'];
$referencePath = $config['sidebar']['directories']['Reference'][0];
$tagsToIgnore = $patterns['class-tags-to-ignore'];

$parser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
$lexer = new Lexer();

function classDocContainsTag(ReflectionClass $class, string $searchedTag, Lexer $lexer, PhpDocParser $parser): bool
{
    $doc = $class->getDocComment();
    if (!$doc) {
        return false;
    }
    $tokens = new TokenIterator($lexer->tokenize($doc));
    $phpDocNode = $parser->parse($tokens);
    $tokens->consumeTokenType(Lexer::TOKEN_END);
    $tags = array_filter($phpDocNode->children, static function (PhpDocChildNode $childNode): bool {
        return $childNode instanceof PhpDocTagNode;
    });
    /** @var PhpDocTagNode $tag */
    foreach ($tags as $tag) {
        if ($searchedTag === $tag->name) {
            return true;
        }
    }
    return false;
}

$files = [];
foreach ($patterns['names'] as $pattern) {
    foreach ((new Finder)->files()->in($root)->name($pattern) as $file) {
        $files[] = $file;
    }
}

foreach ($patterns['directories'] as $pattern) {
    foreach ((new Finder)->files()->in($root.'/'.$pattern)->name("*.php") as $file) {
        $files[] = $file;
    }
}

function containsOnlyPrivateMethods(ReflectionClass $reflectionClass): bool
{
    // Do not skip empty interfaces
    if (interface_exists($reflectionClass->getName()) || trait_exists($reflectionClass->getName())) {
        return false;
    }

    if (!empty($reflectionClass->getProperties())) {
        return false;
    }

    foreach ($reflectionClass->getMethods() as $method) {
        if (!in_array('private', Reflection::getModifierNames($method->getModifiers()))) {
            return false;
        }
    }
    return true;
}

foreach ($files as $file) {
    $relativeToSrc = Path::makeRelative($file->getPath(), $root);
    $relativeToDocs = Path::makeRelative($file->getRealPath(), getcwd());

    $namespace = 'ApiPlatform\\'.str_replace(['/', '.php'], ['\\', ''], $relativeToSrc).'\\'.$file->getBasename('.php');
    foreach ($tagsToIgnore as $tagToIgnore) {
        if (classDocContainsTag(new ReflectionClass($namespace), $tagToIgnore, $lexer, $parser)) {
            continue 2;
        }
    }
    if (containsOnlyPrivateMethods(new ReflectionClass($namespace))) {
        continue;
    }

    exec('mkdir -p '.$referencePath.'/'.$relativeToSrc);
    exec('php src/generate-reference.php '.$relativeToDocs.' > '.$referencePath.'/'.$relativeToSrc.'/'.str_replace('.php','.mdx',$file->getBasename()));
}

<?php

// This script generates a PHP reference from the given namespaces 
// It's configuration can be found in `api-platform.config.js`

require '../vendor/autoload.php';
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocChildNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\UnionTypeNode;
use PHPStan\PhpDocParser\Lexer\Lexer;
use PHPStan\PhpDocParser\Parser\ConstExprParser;
use PHPStan\PhpDocParser\Parser\PhpDocParser;
use PHPStan\PhpDocParser\Ast;
use PHPStan\PhpDocParser\Ast\PhpDoc\ParamTagValueNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use PHPStan\PhpDocParser\Parser\TokenIterator;
use PHPStan\PhpDocParser\Parser\TypeParser;
use Symfony\Component\Finder\Finder;

$parser = new PhpDocParser(new TypeParser(new ConstExprParser()), new ConstExprParser());
$lexer = new Lexer();
$finder = new Finder();

// https://gist.github.com/1franck/5076758
$json = preg_replace("#(/\*([^*]|[\r\n]|(\*+([^*/]|[\r\n])))*\*+/)|([\s\t]//.*)|(^//.*)#", '', file_get_contents('./api-platform.config.json'));
$config = json_decode($json, true)['reference'];
$output = $config['output'];
$stderr = fopen('php://stderr', 'w');

@mkdir($output);

function isAttribute(ReflectionClass $reflectionClass): bool 
{
    foreach($reflectionClass->getAttributes() as $attribute) {
        if ($attribute->getName() === 'Attribute') {
            return true;
        }
    }

    return false;
}

function isImmutable(ReflectionProperty $reflectionProperty, ReflectionClass $reflectionClass): bool 
{
    return $reflectionClass->hasMethod('get'.ucfirst($reflectionProperty->getName())) && $reflectionClass->hasMethod('with'.ucfirst($reflectionProperty->getName()));
}

function getPhpDoc(ReflectionMethod|ReflectionProperty $reflection, PhpDocParser $parser, Lexer $lexer): PhpDocNode
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
        if ($type instanceof ReflectionNamedType && class_exists($type->getName())) {
            return "[$type](/reference/".str_replace(['ApiPlatform\\', '\\'], ['', '/'], $type->getName()).")";
        }

        return sprintf('`%s`', (string) $type);
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

$namespaces = array_flip($config['namespaces'] ?? []);
foreach ($finder->files('*.php')->in($config['src']) as $file) {
    $namespace = 'ApiPlatform\\'.(str_replace(['/', '.php'], ['\\', ''], $file->getRelativePathname()));

    if (!isset($namespaces[$namespace])) {
        continue;
    }

    $content = <<<MDX
import Head from 'next/head';

<Head>
  <title>$namespace</title>
</Head>

MDX;


    $reflectionClass = new ReflectionClass($namespace);

    /** @var ParamTagValueNode[] */
    $propertiesConstructorDocumentation = [];
    $constructorDocumentation = getPhpDoc($reflectionClass->getMethod('__construct'), $parser, $lexer);
    foreach ($constructorDocumentation->getParamTagValues() as $paramTagValueNode) {
        $propertiesConstructorDocumentation[substr($paramTagValueNode->parameterName, 1)] = $paramTagValueNode;
    }

    $content .= "# \\{$reflectionClass->getName()}" . PHP_EOL;

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

        foreach ($text as $t) {
            $content .= $t . PHP_EOL;
        }
    }

    foreach ($reflectionClass->getProperties() as $property) {
        $modifier = getModifier($property);
        if ($modifier === 'private') {
            continue;
        }

        $type = getTypeString($property, $parser, $lexer, $propertiesConstructorDocumentation);
        $content .= "<a className=\"anchor\" href=\"#{$property->getName()}\" id=\"{$property->getName()}\">§</a>".PHP_EOL;
        $content .= "## {$type} \${$property->getName()}" . PHP_EOL;
        if (($propertyConstructor = $properties[$property->getName()] ?? false) && $propertyConstructor->description) {
            $content .= $propertyConstructor->description . PHP_EOL;
        }
        
        $doc = getPhpDoc($property, $parser, $lexer);
		$text = array_filter($doc->children, static function (PhpDocChildNode $child): bool {
			return $child instanceof PhpDocTextNode;
		});

        foreach ($text as $t) {
            $content .= $t . PHP_EOL;
        }
    }
    
    $fileName = str_replace('.php', '.mdx', $file->getRelativePathname());
    @mkdir($output . '/' . dirname($fileName));
    fwrite($stderr, sprintf('Writing %s.%s', $fileName, PHP_EOL));
    file_put_contents($output . '/' . $fileName, $content);

}

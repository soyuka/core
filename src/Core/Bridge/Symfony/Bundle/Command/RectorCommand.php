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

namespace ApiPlatform\Core\Bridge\Symfony\Bundle\Command;

use ApiPlatform\Core\Bridge\Rector\Parser\TransformApiSubresourceVisitor;
use ApiPlatform\Core\Bridge\Rector\Set\ApiPlatformSetList;
use ApiPlatform\Core\Metadata\Resource\Factory\ResourceNameCollectionFactoryInterface;
use ApiPlatform\Core\Operation\Factory\SubresourceOperationFactoryInterface;
use PhpParser\Lexer\Emulative;
use PhpParser\NodeTraverser;
use PhpParser\Parser\Php7;
use PhpParser\PrettyPrinter\Standard;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

/**
 * @experimental
 */
final class RectorCommand extends Command
{
    private const OPERATIONS = [
        'annotation-to-legacy-api-resource' => '@ApiResource to legacy #[ApiResource]',
        'annotation-to-api-resource' => '@ApiResource to new #[ApiResource]',
        'attribute-to-api-resource' => '@ApiResource to legacy #[ApiResource] and new #[ApiResource]',
        'keep-attribute' => 'Legacy #[ApiResource] to new #[ApiResource]',
        'transform-apisubresource' => 'Transform @ApiSubresource',
    ];

    protected static $defaultName = 'api:rector:upgrade';

    private $resourceNameCollectionFactory;
    private $subresourceOperationFactory;
    private $metadataBackwardCompatibilityLayer;
    private $localCache = [];

    public function __construct(ResourceNameCollectionFactoryInterface $resourceNameCollectionFactory, SubresourceOperationFactoryInterface $subresourceOperationFactory, bool $metadataBackwardCompatibilityLayer)
    {
        $this->resourceNameCollectionFactory = $resourceNameCollectionFactory;
        $this->subresourceOperationFactory = $subresourceOperationFactory;
        $this->metadataBackwardCompatibilityLayer = $metadataBackwardCompatibilityLayer;

        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('Change legacy ApiResource annotation/attribute to new ApiResource attribute')
            ->addOption('dry-run', '-d', InputOption::VALUE_NONE, 'Rector will show you diff of files that it would change. To make the changes, drop --dry-run')
            ->addOption('silent', '-s', InputOption::VALUE_NONE, 'Run Rector silently')
            ->addArgument('src', InputArgument::REQUIRED, 'Path to folder/file to convert, forwarded to Rector');

        foreach (self::OPERATIONS as $operationKey => $operationDescription) {
            $this->addOption($operationKey, null, InputOption::VALUE_NONE, $operationDescription);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!file_exists('vendor/bin/rector')) {
            $output->write('Rector is not installed. Please execute composer require --dev rector/rector-src');

            return Command::FAILURE;
        }

        $io = new SymfonyStyle($input, $output);
        $operations = self::OPERATIONS;

        $choices = array_values($operations);

        $choice = null;
        $operationCount = 0;

        foreach ($operations as $operationKey => $operationDescription) {
            if ($input->getOption($operationKey)) {
                $choice = $operationKey;
                ++$operationCount;
            }
        }

        if ($operationCount > 1) {
            $output->write('Only one operation can be given as a parameter.');

            return Command::FAILURE;
        }

        if (!$choice) {
            $choice = $io->choice('Choose operation to perform', $choices);
        }

        $operationKey = $this->getOperationKeyByChoice($operations, $choice);

        $command = 'vendor/bin/rector process '.$input->getArgument('src');

        if ($input->getOption('dry-run')) {
            $command .= ' --dry-run';
        } else {
            if (!$io->confirm('Your files will be overridden. Do you want to continue ?')) {
                $output->write('Migration aborted.');

                return Command::FAILURE;
            }
        }

        if ($output->isDebug()) {
            $command .= ' --debug';
        }

        $operationKeys = array_keys($operations);

        switch ($operationKey) {
            case $operationKeys[0]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_LEGACY_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[1]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[2]:
                $command .= ' --config='.ApiPlatformSetList::ANNOTATION_TO_LEGACY_API_RESOURCE_AND_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[3]:
                $command .= ' --config='.ApiPlatformSetList::ATTRIBUTE_TO_API_RESOURCE_ATTRIBUTE;
                break;
            case $operationKeys[4]:
                $this->transformApiSubresource($input->getArgument('src'));
                $command .= ' --config='.ApiPlatformSetList::TRANSFORM_API_SUBRESOURCE;
                break;
        }

        $io->title('Run '.$command);

        if ($input->getOption('silent')) {
            exec($command);
        } else {
            passthru($command);
        }

        $output->write('Migration successful.');

        return Command::SUCCESS;
    }

    private function getOperationKeyByChoice($operations, $choice): string
    {
        if (\in_array($choice, array_keys($operations), true)) {
            return $choice;
        }

        return array_search($choice, $operations, true);
    }

    private function transformApiSubresource(string $src)
    {
        foreach ($this->resourceNameCollectionFactory->create() as $resourceClass) {
            try {
                new \ReflectionClass($resourceClass);
            } catch (\Exception $e) {
                continue;
            }

            if (!isset($this->localCache[$resourceClass])) {
                $this->localCache[$resourceClass] = [];
            }

            foreach ($this->subresourceOperationFactory->create($resourceClass) as $subresourceMetadata) {
                $identifiers = [];
                // Removing the third tuple element
                foreach ($subresourceMetadata['identifiers'] as $parameterName => [$property, $class, $isPresent]) {
                    if (!$isPresent) {
                        continue;
                    }

                    $identifiers[$parameterName] = [$property, $class];
                }

                $subresourceMetadata['identifiers'] = $identifiers;

                if (!isset($this->localCache[$subresourceMetadata['resource_class']])) {
                    $this->localCache[$subresourceMetadata['resource_class']] = [];
                }

                $this->localCache[$subresourceMetadata['resource_class']][] = $subresourceMetadata;
            }
        }

        foreach ($this->localCache as $resourceClass => $linkedSubresourceMetadata) {
            $fileName = (new \ReflectionClass($resourceClass))->getFilename();

            if (!str_contains($fileName, $src)) {
                continue;
            }

            foreach ($linkedSubresourceMetadata as $subresourceMetadata) {
                $lexer = new Emulative([
                    'usedAttributes' => [
                        'comments',
                        'startLine', 'endLine',
                        'startTokenPos', 'endTokenPos',
                    ],
                ]);
                $parser = new Php7($lexer);

                $traverser = new NodeTraverser();
                $traverser->addVisitor(new TransformApiSubresourceVisitor($subresourceMetadata));
                $prettyPrinter = new Standard();

                $oldStmts = $parser->parse(file_get_contents($fileName));
                $oldTokens = $lexer->getTokens();

                $newStmts = $traverser->traverse($oldStmts);

                $newCode = $prettyPrinter->printFormatPreserving($newStmts, $oldStmts, $oldTokens);

                file_put_contents($fileName, $newCode);
            }
        }
    }
}

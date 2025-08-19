<?php

namespace ApiPlatform\Symfony\Bundle\Command;

use ApiPlatform\Symfony\PhpParser\ApiFilterToParameterVisitor;
use PhpParser\Error;
use PhpParser\NodeTraverser;
use PhpParser\ParserFactory;
use PhpParser\PrettyPrinter;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'api:refactor-filters-to-parameters',
    description: 'Refactor ApiFilter attributes to QueryParameter attributes for a given class.'
)]
final class ApiFilterToParameterCommand extends Command
{
    protected function configure(): void
    {
        $this
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the PHP file to refactor.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filePath = $input->getArgument('path');

        if (!file_exists($filePath) || !is_readable($filePath)) {
            $io->error(sprintf('The file "%s" does not exist or is not readable.', $filePath));

            return Command::FAILURE;
        }

        $code = file_get_contents($filePath);

        $parser = (new ParserFactory())->createForNewestSupportedVersion();
        $traverser = new NodeTraverser();
        $traverser->addVisitor(new ApiFilterToParameterVisitor());

        try {
            $ast = $parser->parse($code);
            $modifiedAst = $traverser->traverse($ast);

            $prettyPrinter = new PrettyPrinter\Standard();
            $newCode = $prettyPrinter->prettyPrintFile($modifiedAst);

            file_put_contents($filePath, $newCode);

            $io->success(sprintf('Successfully refactored ApiFilter attributes in "%s".', $filePath));
        } catch (Error $error) {
            $io->error(sprintf('Parse error: %s', $error->getMessage()));

            return Command::FAILURE;
        }

        return Command::SUCCESS;
    }
}


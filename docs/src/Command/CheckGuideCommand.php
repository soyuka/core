<?php

declare(strict_types=1);

namespace PDG\Command;

use function App\Playground\request;
use PDG\Kernel;
use PHPUnit\TextUI\TestRunner;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pdg:check:guide')]
class CheckGuideCommand extends Command
{
    private const COMMENTARY_REGEX = '/^\s*\/\/\s/';

    private const REQUIRED_HEADERS = [
        'slug',
        'name',
        'position',
        'executable',
    ];

    protected function configure(): void
    {
        $this->addArgument(
            'guide',
            InputArgument::REQUIRED,
            'the path to the guide to test'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $stderr = $io->getErrorStyle();
        $guide = $input->getArgument('guide');
        $kernel = new Kernel('dev', true, $this->getGuideName($guide));

        $stderr->title(\sprintf('Checking guide %s', $this->getGuideName($guide)));

        if (\file_exists($kernel->getCacheDir()) && !$this->deleteCacheDir($kernel->getCacheDir())) {
            $stderr->warning('Can not delete cache dir.');
        }

        if (\file_exists($kernel->getDBDir()) && !$this->deleteCacheDir($kernel->getDBDir())) {
            $stderr->warning('Can not delete cache dir.');
        }

        $handle = \fopen($guide, 'r');
        if (!$handle) {
            $stderr->error('Error opening guide.');

            return Command::INVALID;
        }

        $frontMatterOpen = false;
        $headers = $this->getGuideHeaders($guide);

        if (
            \count($headers) === 0
        ) {
            $stderr->error('No headers detected.');

            return Command::FAILURE;
        }

        $missingHeaders = \array_diff(self::REQUIRED_HEADERS, \array_keys($headers));
        if (\count($missingHeaders) > 0) {
            $stderr->error(\sprintf(
                'Missing required headers: %s',
                \implode(', ', $missingHeaders)
            ));

            return Command::FAILURE;
        }
        $stderr->info('Headers ok.');

        try {
            require $guide;
        } catch (\Throwable $e) {
            $stderr->error(\sprintf(
                'Invalid code: %s.',
                $e->getMessage()
            ));

            return Command::FAILURE;
        }

        if ($headers['executable'] !== 'true') {
            $stderr->warning(\sprintf(
                'Guide %s is not set to be excecutable, skipping.',
                $this->getGuideName($guide),
            ));

            return Command::SUCCESS;
        }
        $stderr->info('Is executable.');

        $migrationClasses = $kernel->getDeclaredClassesForNamespace('DoctrineMigrations');

        if (\count($migrationClasses) > 0) {
            try {
                $kernel->executeMigrations();
            } catch (\Throwable $e) {
                $stderr->error(\sprintf(
                    'Migration error(s): %s.',
                    $e->getMessage()
                ));

                return Command::FAILURE;
            }
        }

        $stderr->info(\sprintf(
            '%s.',
            \count($migrationClasses) > 0
                ? 'Migrations ok' : 'No migrations'
        ));

        if (!\function_exists('\App\Playground\request')) {
            $stderr->error('No request function.');

            return Command::FAILURE;
        }

        $response = $kernel->handle(request());
        if ($response->getStatusCode() >= 500) {
            $stderr->error(\sprintf(
                'Request failed: %d.',
                $response->getStatusCode()
            ));

            return Command::FAILURE;
        }
        $stderr->info('Request ok.');

        $testClasses = $kernel->getDeclaredClassesForNamespace('App\Tests');
        if (\count($testClasses) > 0) {
            $testCommand = new TestGuideCommand();
            $testResult = $testCommand->run(new ArrayInput([
                'guide' => $guide,
            ]), $output);

            if ($testResult !== TestRunner::SUCCESS_EXIT) {
                return Command::FAILURE;
            }
        } else {
            $stderr->warning('No test found in this guide.');
        }

        $stderr->success('All good.');

        return Command::SUCCESS;
    }

    private function getGuideName(string $guide): string
    {
        $expl = \explode('/', $guide);

        return \str_replace('.php', '', \end($expl));
    }

    private function deleteCacheDir(string $directory): bool
    {
        if (!\file_exists($directory)) {
            return true;
        }

        if (!\is_dir($directory)) {
            return \unlink($directory);
        }

        foreach (\scandir($directory) as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            if (!$this->deleteCacheDir($directory.\DIRECTORY_SEPARATOR.$item)) {
                return false;
            }
        }

        return \rmdir($directory);
    }

    /**
     * @return string[]
     */
    private function getGuideHeaders(string $guide): array
    {
        $handle = \fopen($guide, 'r');
        $frontMatterOpen = false;
        $headers = [];
        while (($line = \fgets($handle)) !== false) {
            if (
                !\trim($line)
                || !\preg_match(self::COMMENTARY_REGEX, $line)
                ) {
                continue;
            }

            $text = \preg_replace(self::COMMENTARY_REGEX, '', $line);

            if (\trim($text) === '---') {
                $frontMatterOpen = !$frontMatterOpen;
                if (!$frontMatterOpen) {
                    break;
                }
                continue;
            }

            if ($frontMatterOpen) {
                $header = \explode(':', $text);
                $headers[\trim($header[0])] = \trim($header[1]);
            }
        }

        return $headers;
    }
}

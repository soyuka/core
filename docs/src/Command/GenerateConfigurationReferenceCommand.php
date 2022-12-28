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

namespace PDG\Command;

use ApiPlatform\Symfony\Bundle\DependencyInjection\Configuration;
use Symfony\Component\Config\Definition\Dumper\XmlReferenceDumper;
use Symfony\Component\Config\Definition\Dumper\YamlReferenceDumper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(name: 'pdg:generate-configuration')]
class GenerateConfigurationReferenceCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'output',
            InputArgument::OPTIONAL,
            'The path to the mdx file where the reference will be printed.Leave empty for screen printing'
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $style = new SymfonyStyle($input, $output);
        $style->info('Generating configuration reference');

        $outputFile = $input->getArgument('output');

        $yaml = (new YamlReferenceDumper())->dump(new Configuration());
        $xml = (new XmlReferenceDumper())->dump(new Configuration());
        if (!$xml && !$yaml) {
            $style->error('No configuration is available');

            return Command::FAILURE;
        }

        $content = '# Configuration Reference'.\PHP_EOL;
        $content .= "Here's the complete configuration of the Symfony bundle including default values: ".\PHP_EOL;
        $content .= '```yaml'.\PHP_EOL.$yaml.'```'.\PHP_EOL;
        $content .= 'Or if you prefer XML configuration: '.\PHP_EOL;
        $content .= '```xml'.\PHP_EOL.$xml.'```'.\PHP_EOL;

        if (!fwrite(fopen($outputFile, 'w'), $content)) {
            $style->error('Error opening or writing '.$outputFile);

            return Command::FAILURE;
        }
        $style->success('Configuration reference successfully generated');

        return Command::SUCCESS;
    }
}

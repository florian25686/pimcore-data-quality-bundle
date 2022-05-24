<?php

declare(strict_types=1);

namespace Valantic\DataQualityBundle\Command;

use Pimcore\Console\AbstractCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @codeCoverageIgnore
 */
class TinkerCommand extends AbstractCommand
{
    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this
            ->setName('pimcore:data-quality:tinker')
            ->setDescription('Developer playground for the ValanticDataQualityBundle');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->dump('Hello from ' . self::class);

        return 0;
    }
}

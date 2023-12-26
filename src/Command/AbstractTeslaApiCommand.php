<?php

namespace Moynzzz\TeslaApi\Command;

use Moynzzz\TeslaApi\Enum\ApiRegion;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

abstract class AbstractTeslaApiCommand extends Command
{
    protected SymfonyStyle $io;

    private bool $hasClientIdOption = false;
    private bool $hasClientSecretOption = false;
    private bool $hasRegionOption = false;

    public function addClientIdOption(): static
    {
        $this->addOption('client-id', null, InputOption::VALUE_OPTIONAL, 'The client ID');

        $this->hasClientIdOption = true;

        return $this;
    }

    public function addClientSecretOption(): static
    {
        $this->addOption('client-secret', null, InputOption::VALUE_OPTIONAL, 'The client secret');

        $this->hasClientSecretOption = true;

        return $this;
    }

    public function addRegionOption(): static
    {
        $this->addOption('region', null, InputOption::VALUE_OPTIONAL, 'API region (na, eu, cn)');

        $this->hasRegionOption = true;

        return $this;
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        parent::initialize($input, $output);

        $this->io = new SymfonyStyle($input, $output);
    }

    protected function interact(InputInterface $input, OutputInterface $output): void
    {
        parent::interact($input, $output);

        if ($this->hasClientIdOption && !$input->getOption('client-id')) {
            $input->setOption('client-id', $this->io->ask('Enter Client ID: '));
        }

        if ($this->hasClientSecretOption && !$input->getOption('client-secret')) {
            $input->setOption('client-secret', $this->io->askHidden('Enter Client Secret: '));
        }

        if ($this->hasRegionOption && !$input->getOption('region')) {
            $regions = array_map(static fn(ApiRegion $case) => $case->value, ApiRegion::cases());

            $input->setOption('region', $this->io->choice('Select the API Region:', $regions));
        }
    }

    public function getClientId(InputInterface $input): string
    {
        if (false === $this->hasClientIdOption) {
            throw new \LogicException('The command does not have a client ID option.');
        }

        return $input->getOption('client-id');
    }

    public function getClientSecret(InputInterface $input): string
    {
        if (false === $this->hasClientSecretOption) {
            throw new \LogicException('The command does not have a client secret option.');
        }

        return $input->getOption('client-secret');
    }

    public function getRegion(InputInterface $input): ApiRegion
    {
        if (false === $this->hasRegionOption) {
            throw new \LogicException('The command does not have a region option.');
        }

        $region = ApiRegion::tryFrom($input->getOption('region'));

        if (null === $region) {
            throw new \InvalidArgumentException(sprintf('Region "%s" is invalid.', $input->getOption('region')));
        }

        return $region;
    }
}

<?php

namespace Moynzzz\TeslaApi\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'moynzzz:tesla-api:generate-key-pair',
    description: 'Generate a key pair for the Tesla API',
)]
class GenerateKeyPairCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument(
            'path',
            InputArgument::OPTIONAL,
            'Path to store the key pair.',
            'keys',
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $path = rtrim($input->getArgument('path'), DIRECTORY_SEPARATOR);

        // Check if the directory exists, if not, create it
        if (!is_dir($path)) {
            if (!mkdir($path, 0755, true) && !is_dir($path)) {
                $io->error("Failed to create the directory: $path");

                return Command::FAILURE;
            }

            $io->note("Created directory: $path");
        }

        $privateKeyFilePath = $path . DIRECTORY_SEPARATOR . 'com.tesla.3p.private-key.pem';
        $publicKeyFilePath = $path . DIRECTORY_SEPARATOR . 'com.tesla.3p.public-key.pem';

        // Check if the key files already exist
        if (file_exists($privateKeyFilePath) || file_exists($publicKeyFilePath)) {
            $io->error('One or both of the key files already exist. Please remove them or specify a different path.');

            return Command::FAILURE;
        }

        $io->info('Generating key pair...');

        $config = [
            "private_key_type" => OPENSSL_KEYTYPE_EC,
            "curve_name" => "prime256v1"
        ];

        $privateKeyResource = openssl_pkey_new($config);

        if (!$privateKeyResource) {
            $io->error('Failed to generate the private key');

            return Command::FAILURE;
        }

        if (!openssl_pkey_export_to_file($privateKeyResource, $privateKeyFilePath)) {
            $io->error('Failed to export the private key to a file');

            return Command::FAILURE;
        }

        $keyDetails = openssl_pkey_get_details($privateKeyResource);

        if (!$keyDetails) {
            $io->error('Failed to extract the public key');

            return Command::FAILURE;
        }

        $publicKey = $keyDetails['key'];

        if (file_put_contents($publicKeyFilePath, $publicKey) === false) {
            $io->error('Failed to save the public key to a file');

            return Command::FAILURE;
        }

        $io->success("Private key saved to: $privateKeyFilePath");
        $io->success("Public key saved to: $publicKeyFilePath");

        return Command::SUCCESS;
    }
}

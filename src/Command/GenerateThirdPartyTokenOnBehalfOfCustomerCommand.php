<?php

namespace Moynzzz\TeslaApi\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'moynzzz:tesla-api:generate-third-party-token-on-behalf-of-customer',
    description: 'Generate a third party token on behalf of a customer for the Tesla API',
)]
class GenerateThirdPartyTokenOnBehalfOfCustomerCommand extends AbstractTeslaApiCommand
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    )
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addClientIdOption()
            ->addClientSecretOption()
            ->addRegionOption()
            ->addOption('redirect-uri', null, InputOption::VALUE_OPTIONAL, 'The redirect URI')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $clientId = $this->getClientId($input);
        $clientSecret = $this->getClientSecret($input);
        $region = $this->getRegion($input);
        $redirectUri = $input->getOption('redirect-uri') ?: $this->io->ask('Enter Redirect URI: ');

        $scope = 'openid offline_access user_data vehicle_device_data vehicle_cmds vehicle_charging_cmds';

        $url = 'https://auth.tesla.com/oauth2/v3/authorize';
        $parameters = [
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'scope' => $scope,
            'state' => bin2hex(random_bytes(16)),
            'nonce' => bin2hex(random_bytes(16)),
        ];

        $this->io->writeln('Open the following URL in your browser and log in with the customer\'s Tesla account:');
        $this->io->writeln($url . '?' . http_build_query($parameters));

        $code = $this->io->askHidden('Enter the code from the URL: ');

        try {
            $response = $this->httpClient->request('POST', 'https://auth.tesla.com/oauth2/v3/token', [
                'json' => [
                    'grant_type' => 'authorization_code',
                    'client_id' => $clientId,
                    'client_secret' => $clientSecret,
                    'code' => $code,
                    'audience' => $region->getApiUrl(),
                    'redirect_uri' => $redirectUri,
                    'scope' => $scope,
                ],
            ]);

            if (200 !== $response->getStatusCode()) {
                $this->io->error('Error in token generation: ' . $response->getContent(false));

                return Command::FAILURE;
            }

            $this->io->success('Token generated successfully.');
            $this->io->writeln($response->getContent());

            return Command::SUCCESS;
        } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
            $this->io->error('An error occurred: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }
}

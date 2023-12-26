<?php

namespace Moynzzz\TeslaApi\Command;

use Moynzzz\TeslaApi\Enum\ApiRegion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;

#[AsCommand(
    name: 'moynzzz:tesla-api:generate-partner-authentication-token',
    description: 'Generate a partner authentication token for the Tesla API',
)]
class GeneratePartnerAuthenticationTokenCommand extends Command
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('client-id', null, InputOption::VALUE_OPTIONAL, 'The client ID')
            ->addOption('client-secret', null, InputOption::VALUE_OPTIONAL, 'The client secret')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, 'API region (na, eu, cn)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $questionHelper = $this->getHelper('question');

        $clientId = $input->getOption('client-id') ?:
            $questionHelper->ask($input, $output, new Question('Enter Client ID: '));

        $clientSecretQuestion = (new Question('Enter Client Secret: '))
            ->setHidden(true)
            ->setHiddenFallback(false)
        ;
        $clientSecret = $input->getOption('client-secret') ?:
            $questionHelper->ask($input, $output, $clientSecretQuestion);

        $regionInput = $input->getOption('region');

        if (!$regionInput) {
            $regions = array_map(static fn(ApiRegion $case) => $case->value, ApiRegion::cases());
            $regionQuestion = (new ChoiceQuestion('Select the API Region:', $regions))
                ->setErrorMessage('Region "%s" is invalid.')
            ;
            $regionInput = $questionHelper->ask($input, $output, $regionQuestion);
        }

        $region = ApiRegion::tryFrom($regionInput);

        if (null === $region) {
            $io->error(sprintf('Region "%s" is invalid.', $regionInput));

            return Command::FAILURE;
        }

        $audience = $region->getApiUrl();

        $io->info('Generating partner authentication token...');

        $options = (new HttpOptions())
            ->setBody([
                'grant_type' => 'client_credentials',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'scope' => 'openid vehicle_device_data vehicle_cmds vehicle_charging_cmds',
                'audience' => $audience,
            ])
        ;

        try {
            $response = $this->httpClient->request(
                'POST',
                'https://auth.tesla.com/oauth2/v3/token',
                $options->toArray(),
            );

            if (200 !== $response->getStatusCode()) {
                $io->error('Error in token generation: ' . $response->getContent(false));

                return Command::FAILURE;
            }

            $io->success('Token generated successfully.');
            $io->writeln($response->toArray()['access_token']);

            return Command::SUCCESS;
        } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
            $io->error('An error occurred: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }
}

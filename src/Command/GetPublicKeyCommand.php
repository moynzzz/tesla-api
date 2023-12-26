<?php

namespace Moynzzz\TeslaApi\Command;

use Moynzzz\TeslaApi\Enum\ApiRegion;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\HttpOptions;
use Symfony\Contracts\HttpClient\Exception\HttpExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

#[AsCommand(
    name: 'moynzzz:tesla-api:get-public-key',
    description: 'Get the public key for the Tesla API',
)]
class GetPublicKeyCommand extends Command
{
    public function __construct(
        private HttpClientInterface $httpClient,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('domain', null, InputOption::VALUE_OPTIONAL, 'The domain')
            ->addOption('token', null, InputOption::VALUE_OPTIONAL, 'The token')
            ->addOption('region', null, InputOption::VALUE_OPTIONAL, 'API region (na, eu, cn)')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $questionHelper = $this->getHelper('question');

        $domain = $input->getOption('domain') ?:
            $questionHelper->ask($input, $output, new Question('Enter Domain: '));

        $tokenQuestion = (new Question('Enter Token: '))
            ->setHidden(true)
            ->setHiddenFallback(false)
        ;
        $token = $input->getOption('token') ?:
            $questionHelper->ask($input, $output, $tokenQuestion);

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

        $apiUrl = $region->getApiUrl();

        $this->httpClient = $this->httpClient->withOptions([
            'base_uri' => $apiUrl,
        ]);

        $options = (new HttpOptions())
            ->setAuthBearer($token)
            ->setQuery(['domain' => $domain])
        ;

        try {
            $response = $this->httpClient->request(
                'GET',
                '/api/1/partner_accounts/public_key',
                $options->toArray(),
            );

            if (200 !== $response->getStatusCode()) {
                $io->error('Error in getting public key: ' . $response->getContent(false));

                return Command::FAILURE;
            }

            $io->success('Public key getted successfully.');
            $io->text($response->toArray()['response']['public_key']);

            return Command::SUCCESS;
        } catch (HttpExceptionInterface|TransportExceptionInterface $exception) {
            $io->error('An error occurred: ' . $exception->getMessage());

            return Command::FAILURE;
        }
    }
}

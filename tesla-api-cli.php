#!/usr/bin/env php
<?php

require __DIR__.'/vendor/autoload.php';

use Moynzzz\TeslaApi\Command\GenerateKeyPairCommand;
use Moynzzz\TeslaApi\Command\GeneratePartnerAuthenticationTokenCommand;
use Moynzzz\TeslaApi\Command\GetPublicKeyCommand;
use Moynzzz\TeslaApi\Command\RegisterPartnerAccountCommand;
use Symfony\Component\Console\Application;
use Symfony\Component\HttpClient\HttpClient;
use Symfony\Component\HttpClient\TraceableHttpClient;

$httpClient = HttpClient::create([
    'headers' => [
        'User-Agent' => 'moynzzz-tesla-api/0.0.1',
    ],
]);
$traceableHttpClient = new TraceableHttpClient($httpClient);

$application = new Application();

$application->add(new GenerateKeyPairCommand());

$application->add(new GeneratePartnerAuthenticationTokenCommand($traceableHttpClient));

$application->add(new RegisterPartnerAccountCommand($traceableHttpClient));

$application->add(new GetPublicKeyCommand($traceableHttpClient));

$application->run();

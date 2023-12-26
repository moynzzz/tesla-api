<?php

namespace Moynzzz\TeslaApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TeslaPartnerApi
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function register()
    {

    }

    public function publicKey()
    {

    }
}
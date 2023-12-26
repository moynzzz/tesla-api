<?php

namespace Moynzzz\TeslaApi;

use Symfony\Contracts\HttpClient\HttpClientInterface;

class TeslaApi
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {
    }

    public function partner(): TeslaPartnerApi
    {
        return new TeslaPartnerApi($this->httpClient);
    }
}

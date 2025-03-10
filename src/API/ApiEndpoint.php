<?php

namespace Netitus\Marketo\API;

use Netitus\Marketo\Client\MarketoClientInterface;

class ApiEndpoint
{
    protected MarketoClientInterface $client;
    protected ?string                $version;

    public function __construct(MarketoClientInterface $client)
    {
        $this->client = $client;
        $this->version = 'v' . $client->version();
    }

    protected function restURI(string $endpoint): string
    {
        return '/rest/' . $this->version . $endpoint;
    }

    protected function assetURI(string $endpoint): string
    {
        return '/rest/asset/' . $this->version . $endpoint;
    }

    protected function bulkURI(string $endpoint): string
    {
        return '/bulk/' . $this->version . $endpoint;
    }
}

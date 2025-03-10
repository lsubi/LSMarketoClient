<?php

namespace Netitus\Marketo\API;

use Netitus\Marketo\Client\Response\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Campaigns extends ApiEndpoint
{
    public function getCampaigns(array $query = []): ResponseInterface
    {
        $endpoint = $this->restURI('/campaigns.json');
        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get campaigns: ' . $e);
        }
    }

    public function triggerCampaign(int $campaignId, array $options): ResponseInterface
    {
        $endpoint = $this->restURI('/campaigns/' . $campaignId . '/trigger.json');
        $requestOptions = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json'    => $options,
        ];

        try {
            return $this->client->request('post', $endpoint, $requestOptions);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to trigger campaign: ' . $e);
        }
    }
}

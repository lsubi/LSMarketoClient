<?php

namespace Netitus\Marketo\API;

use Netitus\Marketo\Client\Response\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Partitions extends ApiEndpoint
{
    public function getPartitions(array $query = []): ResponseInterface
    {
        $endpoint = $this->restURI('/leads/partitions.json');

        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get partitions: ' . $e);
        }
    }
}

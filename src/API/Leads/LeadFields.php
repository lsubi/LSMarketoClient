<?php

namespace Netitus\Marketo\API\Leads;

use GuzzleHttp\Exception\RequestException;
use Netitus\Marketo\API\ApiEndpoint;
use Netitus\Marketo\API\Exception\MarketoException;
use Netitus\Marketo\Client\Response\ResponseInterface;

/**
 * @deprecated
 */
class LeadFields extends ApiEndpoint
{
    /** @deprecated  */
    public function getLeadFields(array $query = []): ResponseInterface
    {
        $endpoint = $this->restURI('/leads/describe.json');
        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get lead fields: ' . $e);
        }
    }
}

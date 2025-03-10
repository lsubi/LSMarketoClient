<?php

namespace Netitus\Marketo\API\Leads;

use GuzzleHttp\Exception\RequestException;
use Netitus\Marketo\API\ApiEndpoint;
use Netitus\Marketo\API\Exception\MarketoException;
use Netitus\Marketo\Client\Response\ResponseInterface;

class SalesPersons extends ApiEndpoint
{
    /**
     * @param array $objects
     * @param array $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function upsert(array $objects, array $options = []): ResponseInterface
    {
        $endpoint = $this->restURI('/salespersons.json');
        $requestOptions = [
            'json'    => [
                "action"   => "createOrUpdate",
                "dedupeBy" => "dedupeFields",
                "input"    => $objects,
            ] + $options,
            'headers' => [
                // 'Accepts' => 'application/json'
                'Content-Type' => 'application/json',
            ],
        ];

        try {
            return $this->client->request('post', $endpoint, $requestOptions);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to create or update leads', 0, $e);
        }
    }

    /**
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function describe(): ResponseInterface
    {
        $endpoint = $this->restURI('/salespersons/describe.json');
        try {
            return $this->client->request('get', $endpoint);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to describe salespersons', 0, $e);
        }
    }

    public function query($options = []): ResponseInterface
    {
        $endpoint = $this->restURI('/salespersons.json');
        try {
            return $this->client->request('get', $endpoint, ['query' => $options]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to describe companies', 0, $e);
        }
    }
}

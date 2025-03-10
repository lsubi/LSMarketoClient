<?php

namespace Netitus\Marketo\API\Leads;

use Netitus\Marketo\API\ApiEndpoint;
use Netitus\Marketo\API\Exception\MarketoException;
use Netitus\Marketo\API\Traits\CsvTrait;
use Netitus\Marketo\Client\Response\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Companies extends ApiEndpoint
{
    use CsvTrait;

    /**
     * Describe Lead-Companies API fields
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function describe(): ResponseInterface
    {
        $endpoint = $this->restURI('/companies/describe.json');
        try {
            return $this->client->request('get', $endpoint);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to describe companies', 0, $e);
        }
    }

    /**
     * @param array $objects
     * @param array $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function upsert(array $objects, array $options = []): ResponseInterface
    {
        $endpoint = $this->restURI('/companies.json');
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
     * Bulk upsert leads using CSV upload
     *
     * @param array $header  CSV header row
     * @param array $objects Lead rows
     *
     * @return ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function bulk(array $header, array $objects): ResponseInterface
    {
        $endpoint = $this->bulkURI('/leads.json?format=csv');
        $options = [
            'query'     => [
                'format' => 'csv',
            ],
            'multipart' => [
                [
                    'name'     => 'companies',
                    'filename' => 'companies.csv',
                    'contents' => $this->encodeCsv($header, $objects),
                    'headers'  => [
                        'Content-Type' => 'text/csv',
                    ],
                ],
            ],
        ];

        try {
            user_error($endpoint, E_USER_NOTICE);
            return $this->client->request('post', $endpoint, $options);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to bulk upsert leads', 0, $e);
        }
    }

    /**
     * @param array $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function query(array $options = []): ResponseInterface
    {
        $endpoint = $this->restURI('/companies.json');
        try {
            return $this->client->request('get', $endpoint, ['query' => $options]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to describe companies', 0, $e);
        }
    }
}

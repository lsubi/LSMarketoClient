<?php

namespace Netitus\Marketo\API;

use Netitus\Marketo\API\Traits\CsvTrait;
use Netitus\Marketo\Client\Response\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Leads extends ApiEndpoint
{
    use CsvTrait;

    /**
     * @param array $objects
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\MarketoException
     */
    public function upsert(array $objects): ResponseInterface
    {
        $endpoint = $this->restURI('/leads.json');
        $requestOptions = [
            'json'    => [
                "action"   => "createOrUpdate",
                "dedupeBy" => "dedupeFields",
                "input"    => $objects,
            ],
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
     * @throws \Netitus\Marketo\API\MarketoException
     */
    public function bulkUpsert(array $header, array $objects): ResponseInterface
    {
        $endpoint = $this->bulkURI('/leads.json');
        $options = [
            'query'     => [
                'format' => 'csv',
            ],
            'multipart' => [
                [
                    'name'     => 'leads.csv',
                    'contents' => $this->encodeCsv($header, $objects),
                    'headers'  => [
                        'Content-Type' => 'text/csv',
                    ],
                ],
            ],
        ];

        try {
            return $this->client->request('post', $endpoint, $options);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to bulk upsert leads', 0, $e);
        }
    }

    /**
     * @param int   $programId
     * @param array $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\MarketoException
     */
    public function updateLeadsProgramStatus(int $programId, array $options = []): ResponseInterface
    {
        $endpoint = $this->restURI('/leads/programs/' . $programId . '/status.json');

        $requestOptions = [
            'headers' => [
                'Content-Type' => 'application/json',
            ],
            'json'    => [],
        ];

        foreach ($options as $key => $value) {
            $requestOptions['json'][$key] = $value;
        }

        try {
            return $this->client->request('post', $endpoint, $requestOptions);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to update leads\' program statuses', 0, $e);
        }
    }

    /**
     * @param int   $programId
     * @param array $query
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\MarketoException
     */
    public function getLeadsByProgram(int $programId, array $query = []): ResponseInterface
    {
        // Add &batchSize=1 to test batches of campaigns
        $endpoint = $this->restURI('/leads/programs/' . $programId . '.json');
        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get leads by program', 0, $e);
        }
    }

    
    /**
     * @param int   $programId
     * @param array $query
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\API\MarketoException
     */
    public function getLeadsByFilterType(string $filterType, string $filterValue, $fields = []): ResponseInterface
    {
        // Add &batchSize=1 to test batches of campaigns
        $endpoint = $this->restURI('/leads.json');
        $query['filterType'] = $filterType;
        $query['filterValues'] = $filterValue;
        if (count($fields)) {
            $query['fields'] = implode(',', $fields);
        }
        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get leads by filter', 0, $e);
        }
    }
}

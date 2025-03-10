<?php

namespace Netitus\Marketo\API;

use GuzzleHttp\Exception\RequestException;

class Stats extends ApiEndpoint
{
    /**
     * @return int Total calls made by users
     */
    public function getQuote(): int
    {
        $endpoint = $this->restURI('/stats/usage.json');
        try {
            $res = $this->client->request('get', $endpoint);
            if (!$res->isSuccessful()) {
                throw MarketoException::fromResponse('Retrieving usage stats was unsuccessful', $res);
            }
            $count = 0;
            foreach ($res->getResult()[0]['users'] as $user) {
                $count += $user['count'];
            }
            return $count;
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get usage stats', 0, $e);
        }
    }
}

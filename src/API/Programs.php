<?php

namespace Netitus\Marketo\API;

use Netitus\Marketo\Client\Response\ResponseInterface;
use GuzzleHttp\Exception\BadResponseException;
use GuzzleHttp\Exception\RequestException;
use Netitus\Marketo\Client\Response\AssetResponse;
use Netitus\Marketo\API\Exception\MarketoException;

class Programs extends ApiEndpoint
{
    public function getPrograms(array $query = []): ResponseInterface
    {
        $endpoint = $this->assetURI('/programs.json');
        $query['maxReturn'] = 200;

        try {
            return $this->client->request('get', $endpoint, [
                'query' => $query,
            ]);
        } catch (RequestException $e) {
            throw new MarketoException('Unable to get programs: ' . $e);
        }
    }

    public function clone(int $id, string $name, string $description, int $cloneToFolderId, string $cloneToFolderType): ResponseInterface
    {
        $endpoint = $this->assetURI('/program/' . $id . '/clone.json');
        $body = [
            'name'        => $name,
            'description' => $description,
            'folder'      => json_encode([
                'id'   => $cloneToFolderId,
                'type' => $cloneToFolderType,
            ]),
        ];
        try {
            /** @var AssetResponse $res */
            $res = $this->client->request('post', $endpoint, [
                'form_params' => $body,
            ], AssetResponse::class);
            if (!$res->isSuccessful()) {
                throw MarketoException::fromResponse("Could not clone Program $name", $res);
            }
            $clonedId = $res->getResult()[0]['id'];
            //return $clonedId;
            return $res;
        } catch (BadResponseException $e) {
            throw new MarketoException('Unable to clone Program', 0, $e);
        }
    }
}

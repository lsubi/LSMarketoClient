<?php

namespace Netitus\Marketo\API\Assets;

use GuzzleHttp\Exception\RequestException;
use Netitus\Marketo\API\ApiEndpoint;
use Netitus\Marketo\API\Exception\MarketoException;
use Netitus\Marketo\Client\Response\ResponseInterface;

class Snippets extends ApiEndpoint
{
    public const CONTENT_TYPE_HTML    = 'HTML';
    public const CONTENT_TYPE_DYNAMIC = 'dynamic_content';
    public const CONTENT_TYPE_TEXT    = 'text';

    public function getSnippet(int $snippetId): ResponseInterface
    {
        $endpoint = $this->assetURI("/snippet/$snippetId.json");
        return $this->client->request('get', $endpoint);
    }

    public function querySnippets(string $status): ResponseInterface
    {
        $endpoint = $this->assetURI("/snippets.json");
        return $this->client->request('get', $endpoint, [
            'query' => [
                'status' => $status,
            ],
        ]);
    }

    /**
     * Create Snippet and set its contents
     *
     * @param int    $folderId    Folder ID
     * @param string $name        Snippet name
     * @param string $content     Snippet contents
     * @param string $contentType Content type (default: HTML)
     * @param array  $attributes  Other Snippet attributes
     *
     * @return int Snippet ID
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function createSnippet(
        int $folderId,
        string $name,
        string $content,
        string $contentType = self::CONTENT_TYPE_HTML,
        array $attributes = []
    ): int {
        $endpoint = $this->assetURI('/snippets.json');
        try {
            $res = $this->client->request('post', $endpoint, [
                'form_params' => [
                        'name'   => $name,
                        'folder' => json_encode([
                            'id'   => $folderId,
                            'type' => 'Folder',
                        ]),
                    ] + $attributes,
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ],
            ]);
            $this->evaluateResponse($res);

            if ($res->isSuccessful()) {
                $id = $res->getResult()[0]['id'];
                return $this->updateContent($id, $content, $contentType);
            } else {
                throw MarketoException::fromResponse("'Failed creating snippet '$name'", $res);
            }
        } catch (RequestException $e) {
            throw new MarketoException('Unable to create snippet', 0, $e);
        }
    }

    /**
     * Update snippet attributes / folder
     *
     * Note: Updating folder does not get instant results if you query the snippet
     *
     * @param int   $snippetId Snippet ID
     * @param array $attributes
     * @param int   $folderId  Update the snippet folder
     *
     * @return void
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function updateSnippet(int $snippetId, array $attributes, int $folderId = 0): void
    {
        $endpoint = $this->assetURI("/snippet/$snippetId.json");
        if ($folderId > 0) {
            $attributes['folder'] = json_encode([
                'id'   => $folderId,
                'type' => 'Folder',
            ]);
        }
        try {
            $res = $this->client->request('post', $endpoint, [
                'form_params' => $attributes,
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ],
            ]);
            $this->evaluateResponse($res);

            if (!$res->isSuccessful()) {
                throw MarketoException::fromResponse("Failed updating snippet '$snippetId'", $res);
            }
        } catch (RequestException $e) {
            throw new MarketoException('Unable to update snippet', 0, $e);
        }
    }


    /**
     * Get snippet contents
     *
     * @param int $snippetId Snippet ID
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     */
    public function getContents(int $snippetId)
    {
        $endpoint = $this->assetURI("/snippet/$snippetId/content.json");
        return $this->client->request('get', $endpoint);
    }

    /**
     * Update snippet contents
     *
     * Note: Snippets need to be in draft before updating!
     * Use Snippets::setDraftApproval($snippetID, false) to unapprove before updating
     *
     * @param int    $snippetId Snippet ID
     * @param string $content
     * @param string $contentType
     *
     * @return int
     * @throws \Netitus\Marketo\API\Exception\MarketoException
     */
    public function updateContent(int $snippetId, string $content, string $contentType = self::CONTENT_TYPE_HTML)
    {
        $endpoint = $this->assetURI("/snippet/$snippetId/content.json");

        try {
            $res = $this->client->request('post', $endpoint, [
                'form_params' => [
                    'content' => $content,
                    'type'    => $contentType,
                ],
                'headers'     => [
                    'Content-Type' => 'application/x-www-form-urlencoded; charset=utf-8',
                ],
            ]);
            $this->evaluateResponse($res);

            if ($res->isSuccessful()) {
                return $snippetId;
            } else {
                throw MarketoException::fromResponse('Failed updating snippet contents for ' . $snippetId, $res);
            }
        } catch (RequestException $e) {
            throw new MarketoException('Unable to update snippet contents', 0, $e);
        }
    }

    /**
     * Set draft status of snippet
     *
     * @param int  $snippetId Snippet ID
     * @param bool $approved
     *
     * @return void
     */
    public function setDraftApproval(int $snippetId, bool $approved = true): void
    {
        $endpoint = $this->assetURI("/snippet/$snippetId/approveDraft.json");
        if (!$approved) {
            $endpoint = $this->assetURI("/snippet/$snippetId/unapprove.json");
        }

        $this->client->request('post', $endpoint);
    }
}

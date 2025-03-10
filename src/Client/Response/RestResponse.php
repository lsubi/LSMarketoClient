<?php

namespace Netitus\Marketo\Client\Response;

use Psr\Http\Message\ResponseInterface as PsrResponseInterface;

class RestResponse implements ResponseInterface
{
    protected PsrResponseInterface $response;
    protected ?array               $data = null;

    public function __construct(PsrResponseInterface $response)
    {
        $this->response = $response;
    }

    public function response(): PsrResponseInterface
    {
        return $this->response;
    }

    public function getStatusCode(): int
    {
        return $this->response->getStatusCode();
    }

    public function hasErrors(): bool
    {
        $errors = $this->get('errors', false);
        return !empty($errors);
    }

    public function isSuccessful(): bool
    {
        return $this->get('success', false);
    }

    public function getResult(): array
    {
        return $this->get('result', []);
    }

    public function get(string $key, $default = null)
    {
        return $this->toArray()[$key] ?? $default;
    }

    public function toArray(): array
    {
        if (null === $this->data) {
            $this->data = json_decode($this->response->getBody()->getContents(), true);
        }
        return $this->data;
    }
}

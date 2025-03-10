<?php

namespace Netitus\Marketo\Client\Response;

interface ResponseInterface
{
    public function response(): \Psr\Http\Message\ResponseInterface;
    public function getStatusCode(): int;
    public function hasErrors(): bool;
    public function isSuccessful(): bool;
    public function getResult(): array;
    public function get(string $key, $default = null);
    public function toArray(): array;
}

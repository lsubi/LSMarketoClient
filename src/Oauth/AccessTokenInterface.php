<?php

namespace Netitus\Marketo\Oauth;

interface AccessTokenInterface
{
    public function getToken(): string;

    public function getLastRefresh(): int;

    public function getExpires(): int;

    public function hasExpired(): bool;
}

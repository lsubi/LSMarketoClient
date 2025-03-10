<?php

namespace Netitus\Marketo\Tests\Oauth;

use Netitus\Marketo\Oauth\AccessToken;
use Netitus\Marketo\Oauth\AccessTokenInterface;
use Netitus\Marketo\Oauth\MarketoProvider;
use League\OAuth2\Client\Token\AccessToken as LeagueAccessToken;
use PHPUnit\Framework\TestCase;

class MarketoProviderTest extends TestCase
{
    public function testLeagueAccessTokenFacade()
    {
        $myAccessToken = 'myAccessToken';
        $myExpiresIn = 1234567890;
        $time = time();

        $leagueAccessToken = $this->createMock(LeagueAccessToken::class);
        $leagueAccessToken
            ->expects($this->any())
            ->method('getToken')
            ->willReturn($myAccessToken);
        $leagueAccessToken
            ->expects($this->any())
            ->method('getExpires')
            ->willReturn($myExpiresIn);

        $marketoProvider = $this->createMock(MarketoProvider::class);
        $marketoProvider
            ->expects($this->any())
            ->method('getAccessToken')
            ->willReturn($leagueAccessToken);

        $marketoProvider
            ->expects($this->any())
            ->method('refreshAccessToken')
            ->willReturn(new AccessToken(
                $leagueAccessToken->getToken(),
                $leagueAccessToken->getExpires(),
                $time
            ));

        $expectedToken = new AccessToken($myAccessToken, $myExpiresIn, $time);
        $token = $marketoProvider->refreshAccessToken();
        $this->assertInstanceOf(AccessTokenInterface::class, $token);
        $this->assertEquals($expectedToken, $token);
    }
}

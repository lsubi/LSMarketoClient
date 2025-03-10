<?php

namespace Netitus\Marketo\Tests\Client;

use Netitus\Marketo\Client\MarketoClient;
use Netitus\Marketo\Oauth\AccessToken;
use Netitus\Marketo\Oauth\MarketoProvider;
use Netitus\Marketo\Oauth\RetryAuthorizationTokenFailedException;
use GuzzleHttp\ClientInterface;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class MarketoClientTest extends TestCase
{
    public function testExceptionIsThrownWhenClientRetriesMoreThanMaxRetry()
    {
        $restClient = $this->createMock(ClientInterface::class);
        $provider = $this->createMock(MarketoProvider::class);
        $accessToken = $this->createMock(AccessToken::class);
        $accessToken
            ->expects($this->any())
            ->method('getLastRefresh')
            ->willReturn(1234567890);
        $accessToken
            ->expects($this->any())
            ->method('getExpires')
            ->willReturn(1000);
        $accessToken
            ->expects($this->any())
            ->method('getToken')
            ->willReturn('MOCKACCESSTOKEN');
        $provider
            ->expects($this->any())
            ->method('refreshAccessToken')
            ->willReturn($accessToken);

        $failedResponse = $this->getFailedResponseMock(401);

        $restClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturn($failedResponse);

        $maxRetry = 3;
        $marketoClient = MarketoClient::with(
            $restClient,
            $provider,
            $accessToken,
            function (AccessToken $t) use ($accessToken) {
                $this->assertEquals($t->getToken(), $accessToken->getToken());
            },
            $maxRetry
        );
        try {
            $marketoClient->request('GET', '/example/getExample');
            $this->fail('An exception should have been thrown');
        } catch (RetryAuthorizationTokenFailedException $e) {
        }
        $this->assertEquals(1, 1);
    }

    public function testFailWith401ThenRetryAndSucceedBeforeMaxRetryLimit()
    {
        $restClient = $this->createMock(ClientInterface::class);
        $provider = $this->createMock(MarketoProvider::class);
        $accessToken = $this->createMock(AccessToken::class);
        $accessToken
            ->expects($this->any())
            ->method('getLastRefresh')
            ->willReturn(1234567890);
        $accessToken
            ->expects($this->any())
            ->method('getExpires')
            ->willReturn(1000);
        $accessToken
            ->expects($this->any())
            ->method('getToken')
            ->willReturn('MOCKACCESSTOKEN');
        $provider
            ->expects($this->any())
            ->method('refreshAccessToken')
            ->willReturn($accessToken);

        $failedResponse = $this->getFailedResponseMock(401);
        $successResponse = $this->getResponseMock();

        $restClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failedResponse, $failedResponse, $successResponse);

        $maxRetry = 3;
        $marketoClient = MarketoClient::with(
            $restClient,
            $provider,
            $accessToken,
            function (AccessToken $t) use ($accessToken) {
                $this->assertEquals($t->getToken(), $accessToken->getToken());
            },
            $maxRetry
        );
        $response = $marketoClient->request('GET', '/example/getExample');
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testFailWithMarketoErrorThenRetryAndSucceedBeforeMaxRetryLimit()
    {
        $restClient = $this->createMock(ClientInterface::class);
        $provider = $this->createMock(MarketoProvider::class);
        $accessToken = $this->createMock(AccessToken::class);
        $accessToken
            ->expects($this->any())
            ->method('getLastRefresh')
            ->willReturn(1234567890);
        $accessToken
            ->expects($this->any())
            ->method('getExpires')
            ->willReturn(1000);
        $accessToken
            ->expects($this->any())
            ->method('getToken')
            ->willReturn('MOCKACCESSTOKEN');
        $provider
            ->expects($this->any())
            ->method('refreshAccessToken')
            ->willReturn($accessToken);

        $failedResponse = $this->getFailedResponseMock(200, [
            [
                "code"    => 601,
                "message" => "Access token invalid",
            ],
        ]);
        $successResponse = $this->getResponseMock();
        $restClient
            ->expects($this->exactly(3))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failedResponse, $failedResponse, $successResponse);

        $maxRetry = 3;
        $marketoClient = MarketoClient::with(
            $restClient,
            $provider,
            $accessToken,
            function (AccessToken $t) use ($accessToken) {
                $this->assertEquals($t->getToken(), $accessToken->getToken());
            },
            $maxRetry
        );
        $response = $marketoClient->request('GET', '/example/getExample');
        $this->assertSame(200, $response->getStatusCode());
    }

    private function getResponseMock(int $responseCode = 200)
    {
        $responseStream = $this->createMock(StreamInterface::class);
        $responseStream->expects($this->any())
            ->method('getContents')
            ->willReturn('{"result":[{}]}');

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn($responseCode);
        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($responseStream);

        return $response;
    }

    private function getFailedResponseMock(int $responseCode = 200, array $errors = [])
    {
        $responseBody = [
            'result' => [],
        ];
        if ($errors) {
            $responseBody['errors'] = $errors;
        }

        $responseStream = $this->createMock(StreamInterface::class);
        $responseStream->expects($this->any())
            ->method('getContents')
            ->willReturn(json_encode($responseBody));

        $response = $this->createMock(ResponseInterface::class);
        $response->expects($this->any())
            ->method('getStatusCode')
            ->willReturn($responseCode);
        $response->expects($this->any())
            ->method('getBody')
            ->willReturn($responseStream);

        return $response;
    }

}

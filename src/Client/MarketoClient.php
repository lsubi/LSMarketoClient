<?php

namespace Netitus\Marketo\Client;

use Netitus\Marketo\Client\Response\ResponseInterface;
use Netitus\Marketo\Client\Response\RestResponse;
use Netitus\Marketo\Oauth\AccessToken;
use Netitus\Marketo\Oauth\MarketoProvider;
use Netitus\Marketo\Oauth\MarketoProviderInterface;
use Netitus\Marketo\Oauth\RetryAuthorizationTokenFailedException;
use GuzzleHttp\Client;
use GuzzleHttp\ClientInterface;

class MarketoClient implements MarketoClientInterface
{
    protected const DEFAULT_MAX_RETRY_REQUESTS = 2;
    protected const TOKEN_INVALID              = 601;
    protected const TOKEN_EXPIRED              = 602;

    private ClientInterface          $client;
    private MarketoProviderInterface $provider;
    private ?AccessToken             $accessToken;

    /** @var callable|null */
    private $tokenRefreshCallback;
    private int $maxRetryRequests;

    private function __construct(
        ClientInterface $guzzleClient,
        MarketoProviderInterface $marketoProvider,
        ?callable $tokenRefreshCallback = null,
        ?AccessToken $accessToken = null,
        int $maxRetryRequests
    ) {
        $this->client = $guzzleClient;
        $this->provider = $marketoProvider;
        $this->accessToken = $accessToken ?: new AccessToken('', 0);
        $this->tokenRefreshCallback = $tokenRefreshCallback;
        $this->maxRetryRequests = $maxRetryRequests;
    }

    public static function with(
        ClientInterface $guzzleClient,
        MarketoProviderInterface $marketoProvider,
        ?AccessToken $accessToken = null,
        ?callable $tokenRefreshCallback = null,
        int $maxRetryRequests = null
    ) {
        if (null === $maxRetryRequests) {
            $maxRetryRequests = static::DEFAULT_MAX_RETRY_REQUESTS;
        }
        return new static($guzzleClient, $marketoProvider, $tokenRefreshCallback, $accessToken, $maxRetryRequests);
    }

    public static function withDefaults(
        string $clientId,
        string $clientSecret,
        string $baseUrl,
        ?callable $tokenRefreshCallback = null,
        int $maxRetryRequests = null
    ) {
        if (null === $maxRetryRequests) {
            $maxRetryRequests = static::DEFAULT_MAX_RETRY_REQUESTS;
        }
        $baseUrl = MarketoClient::ensureHttps($baseUrl);
        $baseUrl = MarketoClient::ensureMktorest($baseUrl);
    
        $guzzleClient = new Client([
            'http_errors' => false,
            'base_uri'    => $baseUrl,
            'verify' => false,
        ]);
        $marketoProvider = new MarketoProvider($clientId, $clientSecret, $baseUrl);

        return new static($guzzleClient, $marketoProvider, $tokenRefreshCallback, null, $maxRetryRequests);
    }


    /**
     * Execute an API request
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     * @throws \Netitus\Marketo\Oauth\RetryAuthorizationTokenFailedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function request(string $method, string $uri, array $options = []): ResponseInterface
    {
        return $this->retryRequest($method, $uri, $options);
    }

    /**
     * Marketo API version
     *
     * @return int
     */
    public function version(): int
    {
        return 1; // todo: implement if/when a new Marketo API is released
    }

    /**
     * Refresh token and attempt request up to max retries
     *
     * @param string $method
     * @param string $uri
     * @param array  $options
     *
     * @return \Netitus\Marketo\Client\Response\ResponseInterface
     *
     * @throws \Netitus\Marketo\Oauth\RetryAuthorizationTokenFailedException
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    private function retryRequest(string $method, string $uri, array $options): ResponseInterface
    {
        $attempts = 0;
        do {
            $expirationTime = $this->accessToken->getLastRefresh() + $this->accessToken->getExpires();
            if (time() >= $expirationTime - 300) {
                $this->refreshAccessToken();
            }

            $options['headers']['Authorization'] = 'Bearer ' . $this->accessToken->getToken();
            $response = new RestResponse($this->client->request($method, $uri, $options));

            $isAuthorized = $this->isResponseAuthorized($response);
            $isTokenValid = $this->isTokenValid($response);

            if (!$isAuthorized || !$isTokenValid) {
                $this->refreshAccessToken();
            }
            $attempts++;
        } while ((!$isAuthorized || !$isTokenValid) && $attempts < $this->maxRetryRequests);

        if (!$isAuthorized || !$isTokenValid) {
            throw new RetryAuthorizationTokenFailedException(
                'Max retry limit of ' . $this->maxRetryRequests . 'has been reached. Retrieving access token failed.'
            );
        }

        return $response;
    }

    /**
     * Refresh AccessToken
     *
     * Calls user defined hook to store the AccessToken
     */
    private function refreshAccessToken()
    {
        $tokenResponse = $this->provider->refreshAccessToken();
        $this->accessToken = $tokenResponse;

        if (is_callable($this->tokenRefreshCallback)) {
            call_user_func($this->tokenRefreshCallback, $tokenResponse);
        }
    }

    private function isResponseAuthorized(ResponseInterface $response): bool
    {
        return $response->getStatusCode() !== 401;
    }

    private function isTokenValid(ResponseInterface $response): bool
    {
        /* Depending on the endpoint, the JSON Marketo returns will always contain an errors key (like getPrograms
        does) or will only contain an errors key if there are errors (like getCampaigns does) */
        if ($response->hasErrors()) {
            foreach ($response->get('errors', []) as $error) {
                if ($error['code'] == self::TOKEN_EXPIRED || $error['code'] == self::TOKEN_INVALID) {
                    return false;
                }
            }
        }
        return true;
    }

    public static function ensureHttps($url) {
        if (strpos($url, 'https://') !== 0) { // Check if the string does not start with 'https://'
            $url = 'https://' . $url;        // Prepend 'https://'
        }
        return $url;
    }

    public static function ensureMktorest($url) {
        if (!str_ends_with($url, '.mktorest.com')) {
            $url .= '.mktorest.com'; // Append '.mktorest.com' if not present
        }
        return $url;
    }
}

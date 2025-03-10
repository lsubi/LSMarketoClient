<?php

namespace Netitus\Marketo\API\Exception;

use Netitus\Marketo\Client\Response\ResponseInterface;

/**
 * An exception for Marketo errors
 */
class MarketoException extends \Exception
{
    public static function fromResponse(string $message, ResponseInterface $response, $errorCode = 1)
    {
        return new static($message . '; Response: ' . json_encode($response->toArray()), $errorCode);
    }
}

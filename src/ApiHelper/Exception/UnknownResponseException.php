<?php

namespace ApiHelper\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Class UnknownResponseException.
 */
class UnknownResponseException extends \LogicException
{
    /** @var ResponseInterface */
    protected $response;

    /** @var string */
    protected $contents;

    /**
     * @param ResponseInterface $response
     * @param string            $contents
     */
    public function __construct(ResponseInterface $response, $contents = null)
    {
        $this->response = $response;
        $this->contents = $contents;
        $this->message = sprintf('Api unknown response (%d): %s.', $response->getStatusCode(), $contents);
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}

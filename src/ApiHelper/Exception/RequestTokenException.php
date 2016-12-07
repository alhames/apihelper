<?php

namespace ApiHelper\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Class ApiException.
 *
 * @link http://tools.ietf.org/html/rfc6749#section-5.2
 */
class RequestTokenException extends RequestException
{
    /**
     * invalid_request
     * invalid_client
     * invalid_grant
     * unauthorized_client
     * unsupported_grant_type
     * invalid_scope.
     *
     * @var string
     */
    protected $error;

    /** @var string */
    protected $uri;

    /**
     * @param ResponseInterface $response
     * @param string            $error
     * @param string            $description
     * @param string            $uri
     */
    public function __construct(ResponseInterface $response, $error = null, $description = null, $uri = null)
    {
        parent::__construct($response, sprintf('%s: %s.', $error, $description));
        $this->error = $error;
        $this->uri = $uri;
    }

    /**
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @return string
     */
    public function getUri()
    {
        return $this->uri;
    }
}

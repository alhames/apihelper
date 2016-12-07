<?php

namespace ApiHelper\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Class UnknownContentTypeException.
 */
class UnknownContentTypeException extends RequestException
{
    /** @var string */
    protected $contentType;

    /**
     * @param ResponseInterface $response
     * @param string            $contentType
     */
    public function __construct(ResponseInterface $response, $contentType = null)
    {
        parent::__construct($response, sprintf('Unknown content type: %s.', $contentType));
        $this->contentType = $contentType;
    }

    /**
     * @return string
     */
    public function getContentType()
    {
        return $this->contentType;
    }
}

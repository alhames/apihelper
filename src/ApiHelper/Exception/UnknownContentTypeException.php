<?php

/*
 * This file is part of the API Helper package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

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

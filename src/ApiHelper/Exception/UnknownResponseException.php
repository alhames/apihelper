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
 * Class UnknownResponseException.
 */
class UnknownResponseException extends \LogicException
{
    /** @var ResponseInterface */
    protected $response;

    /** @var string|array */
    protected $contents;

    /**
     * @param ResponseInterface $response
     * @param string|array      $contents
     */
    public function __construct(ResponseInterface $response, $contents = null)
    {
        $this->response = $response;
        $this->contents = $contents;
        $this->message = sprintf('Api unknown response (%d): %s.', $response->getStatusCode(), json_encode($contents));
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}

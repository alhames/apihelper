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
 * Class RequestException.
 */
class RequestException extends \RuntimeException
{
    /** @var ResponseInterface  */
    protected $response;

    /**
     * @param ResponseInterface $response
     * @param string            $message
     * @param int               $code
     */
    public function __construct(ResponseInterface $response, $message = null, $code = null)
    {
        parent::__construct(sprintf('Invalid api request (%d): "%s".', $response->getStatusCode(), $message), $code);
        $this->response = $response;
    }

    /**
     * @return ResponseInterface
     */
    public function getResponse()
    {
        return $this->response;
    }
}

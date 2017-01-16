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
    protected $description;

    /** @var string */
    protected $uri;

    /**
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * @param string $error
     *
     * @return static
     */
    public function setError($error)
    {
        $this->error = $error;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     *
     * @return static
     */
    public function setDescription($description)
    {
        $this->description = $description;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getUri()
    {
        return $this->uri;
    }

    /**
     * @param string $uri
     *
     * @return static
     */
    public function setUri($uri)
    {
        $this->uri = $uri;

        return $this;
    }
}

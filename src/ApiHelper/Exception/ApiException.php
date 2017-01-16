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
 */
class ApiException extends RequestException
{
    /** @var string */
    protected $errorMessage;

    /** @var string|int */
    protected $errorCode;

    /** @var string|array */
    protected $data;

    /**
     * @return string|null
     */
    public function getErrorMessage()
    {
        return $this->errorMessage;
    }

    /**
     * @param string|null $message
     *
     * @return static
     */
    public function setErrorMessage($message)
    {
        $this->errorMessage = $message;

        return $this;
    }

    /**
     * @return string|int|null
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }

    /**
     * @param string|int|null $code
     *
     * @return static
     */
    public function setErrorCode($code)
    {
        $this->errorCode = $code;

        return $this;
    }

    /**
     * @return string|array|null
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @param string|array|null $data
     *
     * @return static
     */
    public function setData($data)
    {
        $this->data = $data;

        return $this;
    }
}

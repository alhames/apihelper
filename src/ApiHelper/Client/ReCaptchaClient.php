<?php

/*
 * This file is part of the API Helper package.
 *
 * (c) Pavel Logachev <alhames@mail.ru>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractClient;
use Psr\Http\Message\ResponseInterface;

/**
 * Class ReCaptchaClient.
 *
 * @link https://developers.google.com/recaptcha/
 */
class ReCaptchaClient extends AbstractClient
{
    /** @var array  */
    protected $lastErrors = [];

    /**
     * @link https://developers.google.com/recaptcha/docs/verify
     *
     * @param string $reCaptchaResponse
     * @param string $remoteIp
     *
     * @return bool
     */
    public function verify($reCaptchaResponse, $remoteIp = null)
    {
        $options = ['secret' => $this->clientSecret, 'response' => $reCaptchaResponse];

        if (null !== $remoteIp) {
            $options['remoteip'] = $remoteIp;
        }

        $result = $this->request('siteverify', $options, 'POST');
        $this->lastErrors = isset($result['error-codes']) ? $result['error-codes'] : [];

        return $result['success'];
    }

    /**
     * @return array
     */
    public function getLastErrors()
    {
        return $this->lastErrors;
    }

    /**
     * {@inheritdoc}
     */
    protected function getRequiredOptions()
    {
        return ['client_id', 'client_secret'];
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://www.google.com/recaptcha/api/'.$method;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
    }
}

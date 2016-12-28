<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractClient;
use ApiHelper\Exception\UnknownResponseException;
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
     * @return array
     */
    protected function getRequiredOptions()
    {
        return ['client_id', 'client_secret'];
    }

    /**
     * @param string $method
     *
     * @return string
     */
    protected function getApiUrl($method)
    {
        return 'https://www.google.com/recaptcha/api/'.$method;
    }

    /**
     * @param ResponseInterface $response
     *
     * @return mixed
     */
    protected function handleResponse(ResponseInterface $response)
    {
        $result = $this->parseResponse($response);

        if ('json' !== $result['type'] || 200 !== $result['status']) {
            throw new UnknownResponseException($response, $result['contents']);
        }

        return json_decode($result['contents'], true);
    }
}

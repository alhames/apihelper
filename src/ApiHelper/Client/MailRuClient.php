<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\InvalidArgumentException;
use ApiHelper\Exception\UnknownResponseException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MailRuClient.
 */
class MailRuClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        $config = [];

        $options['method'] = $apiMethod;
        $options['secure'] = 1;
        $options['app_id'] = $this->clientId;
        $options['session_key'] = $this->accessToken;
        $options['format'] = 'json';

        ksort($options);
        $optionsString = '';
        foreach ($options as $key => $value) {
            $optionsString .= $key.'='.$value;
        }

        $options['sig'] = md5($optionsString.$this->clientSecret);

        if (!empty($options)) {
            if ('POST' === $httpMethod) {
                $config[RequestOptions::FORM_PARAMS] = $options;
            } elseif ('GET' === $httpMethod) {
                $config[RequestOptions::QUERY] = $options;
            } else {
                throw new InvalidArgumentException(sprintf('HTTP method %s is not supported', $httpMethod));
            }
        }

        $response = $this->httpRequest($httpMethod, $this->getApiUrl(''), $config);

        return $this->handleResponse($response);
    }


    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'http://www.appsmail.ru/platform/api';
    }

    /**
     * {@inheritdoc}
     */
    protected function handleResponse(ResponseInterface $response)
    {
        $result = $this->parseResponse($response);

        if ('json' !== $result['type']) {
            throw new UnknownResponseException($response, $result['contents']);
        }

        $data = json_decode($result['contents'], true);

        if (200 === $result['status']) {
            return $data;
        }

        if (400 <= $result['status'] && $result['status'] < 500) {
            throw new ApiException($response, $data['error']['error_msg'], $data['error']['error_code']);
        }

        throw new UnknownResponseException($response, $result['contents']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        return 'https://connect.mail.ru/oauth/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://connect.mail.ru/oauth/token';
    }
}

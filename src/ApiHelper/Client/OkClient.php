<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\InvalidArgumentException;
use ApiHelper\Exception\UnknownResponseException;
use GuzzleHttp\RequestOptions;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OkClient.
 */
class OkClient extends AbstractOAuth2Client
{
    /** {@inheritdoc} */
    protected static $scopeDelimiter = ';';

    /**
     * @todo method не может быть передан в теле POST запроса, он должен передаваться только как параметр URL.
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        $config = [];

        $options['application_key'] = $this->options['application_key'];
        $options['format'] = 'json';
        $options['method'] = $apiMethod;

        ksort($options);
        $optionsString = '';
        foreach ($options as $key => $value) {
            $optionsString .= $key.'='.$value;
        }

        $options['sig'] = md5($optionsString.md5($this->accessToken.$this->clientSecret));
        $options['access_token'] = $this->accessToken;

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
        return 'https://api.ok.ru/fb.do';
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

        if (200 !== $result['status']) {
            throw new UnknownResponseException($response, $result['contents']);
        }

        if (isset($data['error_code'])) {
            throw new ApiException($response, $data['error']['error_msg'], $data['error']['error_code']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        return 'https://connect.ok.ru/oauth/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://api.ok.ru/oauth/token.do';
    }
}

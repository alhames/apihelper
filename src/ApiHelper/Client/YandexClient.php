<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class YandexClient.
 */
class YandexClient extends AbstractOAuth2Client
{
    /**
     * @link https://tech.yandex.ru/passport/doc/dg/reference/request-docpage/
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        if (!isset($options['format'])) {
            $options['format'] = 'json';
        }

        $params['oauth_token'] = $this->accessToken;

        // todo: remove access_token field
        return parent::request($apiMethod, $options, $httpMethod);
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://login.yandex.ru/'.$method;
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

        if (400 <= $result['status'] && $result['status'] < 500) {
            throw new ApiException($response, $data['error']['message'], $data['error']['code']);
        }

        if (200 === $result['status']) {
            return $data;
        }

        throw new UnknownResponseException($response, $result['contents']);
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        return 'https://oauth.yandex.ru/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.yandex.ru/token';
    }
}

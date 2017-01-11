<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class VkClient.
 */
class VkClient extends AbstractOAuth2Client
{
    /** {@inheritdoc} */
    protected $version = '5.45';

    /**
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        if (null !== $this->locale && !isset($options['lang'])) {
            $options['lang'] = $this->locale;
        }

        if (null !== $this->version && !isset($options['v'])) {
            $options['v'] = $this->version;
        }

        return parent::request($apiMethod, $options, $httpMethod)['response']; // todo
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://api.vk.com/method/'.$method;
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

        if (isset($data['error'])) {
            throw new ApiException($response, $data['error']['error_msg'], $data['error']['error_code']);
        }

        return $data;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        if (!isset($query['v'])) {
            $query['v'] = $this->version;
        }

        return 'https://oauth.vk.com/authorize?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://oauth.vk.com/access_token';
    }
}

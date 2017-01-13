<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
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
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        if (null !== $this->locale && !isset($options['lang'])) {
            $options['lang'] = $this->locale;
        }

        if (null !== $this->version && !isset($options['v'])) {
            $options['v'] = $this->version;
        }

        return parent::prepareRequestOptions($options, $apiMethod);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (isset($data['error'])) {
            throw new ApiException($response, $data['error']['error_msg'], $data['error']['error_code']);
        }
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

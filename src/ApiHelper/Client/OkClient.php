<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use ApiHelper\Exception\ApiException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OkClient.
 */
class OkClient extends AbstractOAuth2Client
{
    /** {@inheritdoc} */
    protected static $scopeDelimiter = ';';

    /**
     * {@inheritdoc}
     */
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        $options['method'] = $apiMethod;
        $options['application_key'] = $this->options['application_key'];
        $options['format'] = 'json';

        ksort($options);
        $optionsString = '';
        foreach ($options as $key => $value) {
            $optionsString .= $key.'='.$value;
        }

        $options['sig'] = md5($optionsString.md5($this->accessToken.$this->clientSecret));
        unset($options['method']);

        return parent::prepareRequestOptions($options, $apiMethod);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (isset($data['error_code'])) {
            throw new ApiException($response, $data['error']['error_msg'], $data['error']['error_code']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://api.ok.ru/fb.do?method='.$method;
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

<?php

namespace ApiHelper\Client;

use ApiHelper\Core\AbstractOAuth2Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class MailRuClient.
 */
class MailRuClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
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

        return $options;
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (400 <= $statusCode && $statusCode < 500) {
            throw $this->createApiException($response, $data, $data['error']['error_code'], $data['error']['error_msg']);
        }
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

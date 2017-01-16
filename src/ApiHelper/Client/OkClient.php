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

use ApiHelper\Core\AbstractOAuth2Client;
use Psr\Http\Message\ResponseInterface;

/**
 * Class OkClient.
 *
 * @link https://apiok.ru/dev/
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
        $options['application_key'] = $this->options['application_key'];
        $options['format'] = 'json';

        ksort($options);
        $optionsString = '';
        foreach ($options as $key => $value) {
            $optionsString .= $key.'='.$value;
        }

        $sessionSecretKey = null === $this->accessToken ? $this->clientSecret : md5($this->accessToken.$this->clientSecret);
        $options['sig'] = md5($optionsString.$sessionSecretKey);

        return parent::prepareRequestOptions($options, $apiMethod);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (isset($data['error_code'])) {
            throw $this->createApiException($response, $data, $data['error_code'], $data['error_msg']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://api.ok.ru/api/'.$method;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        if (!isset($query['layout']) && isset($this->options['layout'])) {
            $query['layout'] = $this->options['layout'];
        }

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

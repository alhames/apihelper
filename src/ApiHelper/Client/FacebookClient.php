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
use ApiHelper\Exception\ApiException;
use ApiHelper\Exception\UnknownResponseException;
use Psr\Http\Message\ResponseInterface;

/**
 * Class FacebookClient.
 */
class FacebookClient extends AbstractOAuth2Client
{
    /** {@inheritdoc} */
    protected static $scopeDelimiter = ',';

    /** {@inheritdoc} */
    protected $redirectUri = 'https://www.facebook.com/connect/login_success.html';

    /** {@inheritdoc} */
    protected $version = '2.8';

    /**
     * {@inheritdoc}
     */
    public function request($apiMethod, array $options = [], $httpMethod = 'GET')
    {
        if (null !== $this->locale) {
            $options['locale'] = $this->locale;
        }

        return parent::request($apiMethod, $options, $httpMethod);
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

        if (400 === $result['status']) {
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
        return 'https://www.facebook.com/v'.$this->version.'/dialog/oauth?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://graph.facebook.com/v'.$this->version.'/oauth/access_token';
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://graph.facebook.com/v'.$this->version.'/'.$method;
    }
}

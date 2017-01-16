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
 * Class FacebookClient.
 *
 * @link https://developers.facebook.com/docs/graph-api
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
    protected function prepareRequestOptions(array $options, $apiMethod)
    {
        if (null !== $this->locale && !isset($options['locale'])) {
            $options['locale'] = $this->locale;
        }

        return parent::prepareRequestOptions($options, $apiMethod);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (400 <= $statusCode && $statusCode < 500) {
            throw $this->createApiException($response, $data, $data['error']['code'], $data['error']['message']);
        }
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

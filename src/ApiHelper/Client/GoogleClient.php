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
use Psr\Http\Message\ResponseInterface;

/**
 * Class GoogleClient.
 */
class GoogleClient extends AbstractOAuth2Client
{
    /**
     * {@inheritdoc}
     */
    public function getAuthorizationUrl($state = null, array $params = [])
    {
        if (!isset($params['access_type']) && isset($this->options['access_type'])) {
            $params['access_type'] = $this->options['access_type'];
        }

        if (!isset($params['prompt']) && isset($this->options['prompt'])) {
            $params['prompt'] = $this->options['prompt'];
        }

        if (!isset($params['login_hint']) && isset($this->options['login_hint'])) {
            $params['login_hint'] = $this->options['login_hint'];
        }

        if (!isset($params['include_granted_scopes']) && isset($this->options['include_granted_scopes'])) {
            $params['include_granted_scopes'] = $this->options['include_granted_scopes'];
        }

        return parent::getAuthorizationUrl($state, $params);
    }

    /**
     * {@inheritdoc}
     */
    protected function checkResponseError($statusCode, $data, ResponseInterface $response)
    {
        if (400 <= $statusCode && $statusCode < 500) {
            throw new ApiException($response, $data['error']['message'], $data['error']['code']);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function getApiUrl($method)
    {
        return 'https://www.googleapis.com/'.$method;
    }

    /**
     * {@inheritdoc}
     */
    protected function getAuthorizeUrl(array $query)
    {
        return 'https://accounts.google.com/o/oauth2/v2/auth?'.http_build_query($query);
    }

    /**
     * {@inheritdoc}
     */
    protected function getTokenUrl()
    {
        return 'https://www.googleapis.com/oauth2/v4/token';
    }
}
